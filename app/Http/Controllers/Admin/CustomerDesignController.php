<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerDesign;
use App\Models\Customers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Katalog design milik customer.
 *
 * Design di sini diunggah sekali lalu dipakai berulang: di modul Design,
 * operator tinggal memilih design customer yang sudah ada ketimbang
 * meng-upload ulang gambar yang sama.
 */
class CustomerDesignController extends Controller
{
    private const UPLOAD_DIR = 'uploads/customer-designs';

    public function index()
    {
        return view('erp.pages.designs.customer-designs.index');
    }

    /**
     * Daftar customer beserta design-nya (tabel di dalam tabel).
     *
     * Barisnya customer, bukan design: customer yang belum punya design pun
     * tetap muncul supaya operator bisa langsung menambahkan design dari sana.
     * Filter tanggal baru menyaring customer, karena begitu tanggal dipilih
     * yang dicari memang customer yang punya design di rentang itu.
     */
    public function data(Request $request)
    {
        $length = (int) $request->input('length', 15);
        $start = (int) $request->input('start', 0);

        $dateRange = $this->dateRange($request);
        $keyword = trim((string) $request->input('search_keyword'));

        $designFilter = function ($query) use ($dateRange) {
            if ($dateRange) {
                $query->whereBetween('customer_designs.created_at', $dateRange);
            }
        };

        $query = Customers::query()
            ->withCount(['designs' => $designFilter])
            ->with(['designs' => function ($q) use ($designFilter) {
                $designFilter($q);
                $q->orderBy('created_at', 'desc');
            }]);

        if ($request->filled('customer_id')) {
            $query->where('id', $request->customer_id);
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword, $designFilter) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('designs', function ($sub) use ($keyword, $designFilter) {
                        $designFilter($sub);
                        $sub->where(function ($inner) use ($keyword) {
                            $inner->where('title', 'like', "%{$keyword}%")
                                ->orWhere('notes', 'like', "%{$keyword}%");
                        });
                    });
            });
        }

        // Rentang tanggal dipilih berarti operator sedang mencari design, jadi
        // customer tanpa design di rentang itu tidak perlu ikut ditampilkan.
        if ($dateRange) {
            $query->whereHas('designs', $designFilter);
        }

        // Daftarnya panjang dan dibaca sambil mencari nama, jadi urutkan
        // menurut abjad — bukan menurut design terbaru.
        $query->orderBy('name');

        [$rows, $hasMore] = $this->lazyLoadPage($query, $start, $length);

        return response()->json([
            'data' => $rows->map(function (Customers $customer) {
                $latest = $customer->designs->first();

                return [
                    'id' => $customer->id,
                    'customer' => $customer->name,
                    'phone' => $customer->phone,
                    'total_designs' => $customer->designs_count,
                    'total_images' => $customer->designs->sum(fn (CustomerDesign $design) => count($design->imageList())),
                    'latest_at' => $latest?->created_at?->format('d M Y H:i'),
                    'designs_html' => view('erp.pages.designs.customer-designs.partials.design-list', [
                        'customer' => $customer,
                    ])->render(),
                ];
            }),
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Terjemahkan pilihan filter tanggal jadi rentang [awal, akhir].
     *
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}|null
     */
    private function dateRange(Request $request): ?array
    {
        switch ($request->input('filter')) {
            case 'today':
                return [Carbon::today(), Carbon::today()->endOfDay()];
            case 'last_7_days':
                return [Carbon::now()->subDays(7), Carbon::now()];
            case 'this_month':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'last_30_days':
                return [Carbon::now()->subDays(30), Carbon::now()];
            case 'year_to_date':
                return [Carbon::now()->startOfYear(), Carbon::now()];
            case 'yearly':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    return [
                        Carbon::parse($request->start_date)->startOfDay(),
                        Carbon::parse($request->end_date)->endOfDay(),
                    ];
                }

                return null;
            default:
                return null;
        }
    }

    /**
     * Sumber data select2 customer. Dibatasi 25 baris supaya dropdown-nya
     * tidak menarik seluruh tabel customer setiap kali dibuka.
     */
    public function customers(Request $request)
    {
        $keyword = trim((string) $request->input('q'));

        $customers = Customers::query()
            ->when($keyword !== '', fn ($q) => $q->where('name', 'like', "%{$keyword}%"))
            ->orderBy('name')
            ->limit(25)
            ->get(['id', 'name', 'phone']);

        return response()->json([
            'results' => $customers->map(fn ($customer) => [
                'id' => $customer->id,
                'text' => $customer->phone ? "{$customer->name} ({$customer->phone})" : $customer->name,
            ]),
        ]);
    }

    public function show($id)
    {
        $design = CustomerDesign::with('customer')->findOrFail($id);

        return response()->json([
            'id' => $design->id,
            'customer_id' => $design->customer_id,
            'customer_name' => $design->customer?->name ?? '-',
            'title' => $design->title,
            'notes' => $design->notes,
            'images' => $design->imageList(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'image_notes' => 'array',
        ]);

        try {
            $design = CustomerDesign::create([
                'customer_id' => $request->customer_id,
                'title' => $request->title,
                'notes' => $request->notes,
                'images' => $this->storeImages($request),
                'created_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Design customer berhasil disimpan.',
                'id' => $design->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan design customer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan design customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'images' => 'array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
            'image_notes' => 'array',
            'kept_images' => 'array',
            'kept_images.*' => 'integer|min:0',
            'kept_image_notes' => 'array',
        ]);

        $design = CustomerDesign::findOrFail($id);

        try {
            // Gambar lama yang dihapus di form tidak dikirim balik, jadi daftar
            // akhir = yang masih ada di kept_images + hasil upload baru.
            $existing = $design->imageList();
            $keptIndexes = array_map('intval', $request->input('kept_images', []));
            $keptNotes = $request->input('kept_image_notes', []);

            $images = [];

            foreach ($keptIndexes as $index) {
                if (! isset($existing[$index])) {
                    continue;
                }

                $images[] = [
                    'file' => $existing[$index]['file'],
                    'note' => (string) ($keptNotes[$index] ?? $existing[$index]['note']),
                ];
            }

            $images = array_merge($images, $this->storeImages($request));

            if (empty($images)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Design harus punya minimal 1 gambar.',
                ], 422);
            }

            $design->update([
                'customer_id' => $request->customer_id,
                'title' => $request->title,
                'notes' => $request->notes,
                'images' => $images,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Design customer berhasil diperbarui.',
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui design customer: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui design customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $design = CustomerDesign::findOrFail($id);

        // Sengaja soft delete tanpa menghapus file: gambarnya bisa saja sudah
        // terpasang di design item yang sedang berjalan.
        $design->delete();

        return response()->json([
            'success' => true,
            'message' => 'Design customer berhasil dihapus.',
        ]);
    }

    /**
     * Simpan file upload ke folder publik dan kembalikan entri [{file, note}].
     *
     * @return array<int, array{file: string, note: string}>
     */
    private function storeImages(Request $request): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        $uploadPath = public_path(self::UPLOAD_DIR);

        if (! file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $notes = $request->input('image_notes', []);
        $stored = [];

        foreach ($request->file('images') as $index => $image) {
            $fileName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($uploadPath, $fileName);

            $stored[] = [
                'file' => self::UPLOAD_DIR . '/' . $fileName,
                'note' => (string) ($notes[$index] ?? ''),
            ];
        }

        return $stored;
    }
}
