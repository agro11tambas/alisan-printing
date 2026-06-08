<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
