<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceMode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PriceModeController extends Controller
{
    public function index()
    {
        return view('erp.pages.price-modes.index');
    }

    public function data(Request $request)
    {
        $length = (int) $request->input('length', 50);
        $start = (int) $request->input('start', 0);

        $query = PriceMode::query()
            ->when($request->filled('search_keyword'), fn ($builder) => $builder
                ->where(fn ($inner) => $inner
                    ->where('name', 'like', '%' . $request->search_keyword . '%')
                    ->orWhere('slug', 'like', '%' . $request->search_keyword . '%')))
            ->orderBy('sort_order')
            ->orderBy('name');

        $totalData = (clone $query)->count();
        $modes = $query->skip($start)->take($length)->get();

        return response()->json([
            'data' => $modes->map(fn ($mode) => [
                'name' => e($mode->name),
                'code' => e($mode->slug),
                'status' => $mode->is_active
                    ? '<span class="badge bg-soft-success text-success">Active</span>'
                    : '<span class="badge bg-soft-secondary text-secondary">Inactive</span>',
                'action' => view('erp.pages.price-modes.partials.action-button', compact('mode'))->render(),
            ]),
            'has_more' => $totalData > ($start + $length),
        ]);
    }

    public function create()
    {
        return view('erp.pages.price-modes.form', ['priceMode' => new PriceMode()]);
    }

    public function store(Request $request)
    {
        PriceMode::create($this->validatedData($request));

        return redirect('/erp/products/price-modes')->with('success', 'Mode berhasil dibuat.');
    }

    public function edit(PriceMode $priceMode)
    {
        return view('erp.pages.price-modes.form', compact('priceMode'));
    }

    public function update(Request $request, PriceMode $priceMode)
    {
        $priceMode->update($this->validatedData($request, $priceMode));

        return redirect('/erp/products/price-modes')->with('success', 'Mode berhasil diperbarui.');
    }

    public function delete(PriceMode $priceMode)
    {
        if ($priceMode->productUnitPrices()->exists() || $priceMode->orderItems()->exists() || $priceMode->purchaseItems()->exists()) {
            return back()->with('error', 'Mode sudah dipakai. Nonaktifkan mode ini agar histori tetap aman.');
        }

        $priceMode->delete();

        return back()->with('success', 'Mode berhasil dihapus.');
    }

    private function validatedData(Request $request, ?PriceMode $priceMode = null): array
    {
        $request->merge([
            'slug' => Str::slug($request->input('slug') ?: $request->input('name')),
            'is_active' => $request->boolean('is_active'),
            'production_flow' => 'production',
        ]);

        return $request->validate([
            'name' => 'required|string|max:100',
            'slug' => ['required', 'string', 'max:100', Rule::unique('price_modes', 'slug')->ignore($priceMode?->id)],
            'sort_order' => 'required|integer|min:0',
            'production_flow' => 'required|in:production',
            'is_active' => 'required|boolean',
        ]);
    }
}
