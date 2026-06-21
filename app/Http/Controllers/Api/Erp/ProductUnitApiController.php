<?php

namespace App\Http\Controllers\Api\Erp;

use App\Http\Controllers\Controller;
use App\Models\ProductUnit;
use Illuminate\Http\Request;

class ProductUnitApiController extends Controller
{
    public function index(Request $request)
    {
        $units = ProductUnit::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Product units retrieved successfully',
            'data' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_units,name',
            'description' => 'nullable|string',
        ]);

        $unit = ProductUnit::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product unit created successfully',
            'data' => $unit,
        ], 201);
    }

    public function show($id)
    {
        $unit = ProductUnit::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Product unit retrieved successfully',
            'data' => $unit,
        ]);
    }

    public function update(Request $request, $id)
    {
        $unit = ProductUnit::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:product_units,name,' . $unit->id,
            'description' => 'nullable|string',
        ]);

        $unit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product unit updated successfully',
            'data' => $unit,
        ]);
    }

    public function destroy($id)
    {
        $unit = ProductUnit::findOrFail($id);
        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product unit deleted successfully',
        ]);
    }
}
