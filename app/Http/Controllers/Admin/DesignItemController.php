<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerDesign;
use App\Models\DesignItem;
use Illuminate\Http\Request;

class DesignItemController extends Controller
{
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
