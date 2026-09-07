<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerDesign;
use App\Models\DesignItem;
use Illuminate\Http\Request;

class DesignItemController extends Controller
{
    private const UPLOAD_DIR = 'uploads/designs';

    // public function upload(Request $request, $id)
    // {
    //     $request->validate([
    //         'preview_image'   => 'required|array',
    //         'preview_image.*' => 'image|mimes:jpg,jpeg,png|max:2048',
    //         'note'            => 'nullable|string',
    //     ]);

    //     $item = DesignItem::findOrFail($id);

    //     $imagePaths = [];

    //     if ($request->hasFile('preview_image')) {
    //         foreach ($request->file('preview_image') as $image) {
    //             $fileName = time() . '_' . $image->getClientOriginalName();
    //             $image->move(public_path('uploads/designs'), $fileName);
    //             $imagePaths[] = $fileName;
    //         }

    //         // simpan ke kolom JSON
    //         $item->preview_image = json_encode($imagePaths);
    //     }

    //     $item->note = $request->note;
    //     $item->save();

    //     return response()->json(['message' => 'Image uploaded successfully!']);
    // }

    public function upload(Request $request, $id)
    {
        $request->validate([
            'preview_image'   => 'required|array',
            'preview_image.*' => 'image|mimes:jpg,jpeg,png|max:4096',
            'note_per_image'  => 'array',
        ]);

        $item = DesignItem::findOrFail($id);

        $uploadedImages = [];
        $notes = $request->note_per_image ?? [];

        if ($request->hasFile('preview_image')) {
            // ✅ simpan di folder uploads/designs (satu level di atas /public)
            $uploadPath = public_path('uploads/designs');

            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            foreach ($request->file('preview_image') as $index => $image) {
                $fileName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $fileName);

                $uploadedImages[] = [
                    'file' => 'uploads/designs/' . $fileName, // simpan path relatif
                    'note' => $notes[$index] ?? '',
                ];
            }

            // simpan struktur JSON: [ {file, note}, ... ]
            $item->preview_image = json_encode($uploadedImages);
        }

        $item->save();

        return response()->json(['message' => 'Image(s) uploaded successfully!']);
    }

    /**
     * Hapus preview design item: satu gambar (kirim `index`) atau semuanya.
     *
     * File fisiknya ikut dihapus hanya kalau berada di folder upload halaman
     * Design. Preview yang dipasang lewat "Design Customer" menunjuk ke file
     * milik katalog customer — file itu masih dipakai record CustomerDesign
     * dan bisa dipakai design item lain, jadi di sini cukup dilepas
     * referensinya.
     */
    public function destroyPreview(Request $request, $id)
    {
        $request->validate([
            'index' => 'nullable|integer|min:0',
        ]);

        $item = DesignItem::findOrFail($id);

        $images = $this->previewList($item);

        if ($images === []) {
            return response()->json([
                'message' => 'Design item ini belum punya preview.',
            ], 422);
        }

        $index = $request->input('index');

        if ($index === null) {
            $removed = $images;
            $remaining = [];
        } else {
            $index = (int) $index;

            if (! array_key_exists($index, $images)) {
                return response()->json([
                    'message' => 'Gambar yang dipilih tidak ditemukan pada preview ini.',
                ], 422);
            }

            $removed = [$images[$index]];
            $remaining = $images;
            unset($remaining[$index]);
            $remaining = array_values($remaining);
        }

        foreach ($removed as $image) {
            $this->deleteOwnedPreviewFile($image['file']);
        }

        $item->preview_image = $remaining === [] ? null : json_encode($remaining);
        $item->save();

        return response()->json([
            'message' => $index === null
                ? 'Semua preview berhasil dihapus.'
                : 'Preview berhasil dihapus.',
            'remaining' => count($remaining),
        ]);
    }

    /**
     * Isi kolom preview_image yang sudah dipastikan berbentuk [{file, note}, ...].
     *
     * Kolomnya JSON bebas dan pernah diisi beberapa versi kode yang berbeda,
     * jadi baris lama/rusak jangan sampai bikin penghapusan error.
     *
     * @return array<int, array{file: string, note: string}>
     */
    private function previewList(DesignItem $item): array
    {
        $images = json_decode($item->preview_image ?? '[]', true);

        if (! is_array($images)) {
            return [];
        }

        $clean = [];

        foreach ($images as $image) {
            if (! is_array($image) || empty($image['file'])) {
                continue;
            }

            $clean[] = [
                'file' => (string) $image['file'],
                'note' => (string) ($image['note'] ?? ''),
            ];
        }

        return $clean;
    }

    /**
     * Buang file preview dari disk, tapi hanya yang memang milik halaman ini.
     */
    private function deleteOwnedPreviewFile(string $file): void
    {
        $file = ltrim(str_replace('\\', '/', $file), '/');

        // `..` di path akan membawa unlink() keluar dari folder upload.
        if (! str_starts_with($file, self::UPLOAD_DIR.'/') || str_contains($file, '..')) {
            return;
        }

        $path = public_path($file);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Katalog design milik customer pemilik design item ini.
     *
     * Dipakai modal "Pilih Design Customer" di halaman Design supaya operator
     * tidak perlu upload ulang gambar yang sudah pernah dikirim customer.
     */
    public function customerDesigns($id)
    {
        $item = DesignItem::with('design.order.customer')->findOrFail($id);

        $customer = $item->design?->order?->customer;

        if (! $customer) {
            return response()->json([
                'customer' => null,
                'data' => [],
            ]);
        }

        $designs = CustomerDesign::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'customer' => $customer->name,
            'data' => $designs->map(fn (CustomerDesign $design) => [
                'id' => $design->id,
                'title' => $design->title,
                'notes' => $design->notes,
                'images' => $design->imageList(),
                'created_at' => optional($design->created_at)->format('d M Y'),
            ])->values(),
        ]);
    }

    /**
     * Pasang satu design dari katalog customer ke design item.
     *
     * Satu design item hanya memakai satu design, jadi pilihan yang masuk
     * menggantikan preview yang ada — bukan menambah.
     *
     * Yang dikirim klien hanya id design + indeks gambarnya; path file selalu
     * dibaca ulang dari database, jadi klien tidak bisa menyisipkan path
     * sembarangan. Design yang tidak dimiliki customer terkait ikut ditolak.
     */
    public function attachCustomerDesign(Request $request, $id)
    {
        $request->validate([
            'design_id' => 'required|integer',
            'index' => 'required|integer|min:0',
            'note' => 'nullable|string',
        ]);

        $item = DesignItem::with('design.order')->findOrFail($id);

        $customerId = $item->design?->order?->customer_id;

        if (! $customerId) {
            return response()->json([
                'message' => 'Design item ini tidak terhubung ke customer mana pun.',
            ], 422);
        }

        $design = CustomerDesign::where('customer_id', $customerId)
            ->whereKey($request->input('design_id'))
            ->first();

        if (! $design) {
            return response()->json([
                'message' => 'Design yang dipilih tidak tersedia untuk customer pada order ini.',
            ], 422);
        }

        $image = $design->imageList()[$request->integer('index')] ?? null;

        if (! $image) {
            return response()->json([
                'message' => 'Gambar yang dipilih tidak ditemukan pada design tersebut.',
            ], 422);
        }

        $note = trim((string) $request->input('note'));

        $item->preview_image = json_encode([[
            'file' => $image['file'],
            'note' => $note !== '' ? $note : ($image['note'] ?: $design->title),
        ]]);
        $item->save();

        return response()->json([
            'message' => 'Design "' . $design->title . '" berhasil dipasang.',
        ]);
    }
}
