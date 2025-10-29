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
    //         'preview_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    //         'note' => 'nullable|string',
    //     ]);

    //     $item = DesignItem::findOrFail($id);

    //     // Upload file
    //     if ($request->hasFile('preview_image')) {
    //         $file = $request->file('preview_image');
    //         $filename = time() . '_' . $file->getClientOriginalName();
    //         $file->move(public_path('uploads/designs'), $filename);
    //         $item->preview_image = $filename;
    //     }

    //     $item->note = $request->note;
    //     $item->save();

    //     return response()->json(['message' => 'Image uploaded successfully!']);
    // }

    public function upload(Request $request, $id)
    {
        $request->validate([
            'preview_image'   => 'required|array',
            'preview_image.*' => 'image|mimes:jpg,jpeg,png|max:2048',
            'note'            => 'nullable|string',
        ]);

        $item = DesignItem::findOrFail($id);

        $imagePaths = [];

        if ($request->hasFile('preview_image')) {
            foreach ($request->file('preview_image') as $image) {
                $fileName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/designs'), $fileName);
                $imagePaths[] = $fileName;
            }

            // simpan ke kolom JSON
            $item->preview_image = json_encode($imagePaths);
        }

        $item->note = $request->note;
        $item->save();

        return response()->json(['message' => 'Image uploaded successfully!']);
    }
}
