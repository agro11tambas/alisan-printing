<?php

namespace App\Http\Controllers\Api\Erp;

use App\Http\Controllers\Controller;
use App\Models\ProductTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductTagApiController extends Controller
{
    public function index(Request $request)
    {
        $tags = ProductTag::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('slug', 'like', '%' . $request->search . '%');
            })
            ->orderBy('name', 'asc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Product tags retrieved successfully',
            'data' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_tags,slug',
            'description' => 'nullable|string',
        ]);

        $tag = ProductTag::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product tag created successfully',
            'data' => $tag,
        ], 201);
    }

    public function show($id)
    {
        $tag = ProductTag::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Product tag retrieved successfully',
            'data' => $tag,
        ]);
    }

    public function update(Request $request, $id)
    {
        $tag = ProductTag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_tags,slug,' . $tag->id,
            'description' => 'nullable|string',
        ]);

        $tag->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product tag updated successfully',
            'data' => $tag,
        ]);
    }

    public function destroy($id)
    {
        $tag = ProductTag::findOrFail($id);
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product tag deleted successfully',
        ]);
    }
}
