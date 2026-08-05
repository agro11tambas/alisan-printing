<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Discount;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\Products;
use App\Models\ProductCategory;
use App\Models\PriceMode;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiscountController extends Controller
{
    public function getDiscount()
    {
        return view('erp.pages.discounts.index');
    }
    public function dataDiscount()
    {
        $discount = Discount::with([
            'products:id,name',
            'categories:id,name',
            'priceModes:id,name',
        ])->get();

        return DataTables::of($discount)
            ->addIndexColumn()
            ->addColumn('name', function ($discount) {
                return $discount->name;
            })
            ->addColumn('type', function ($discount) {
                return $discount->type;
            })
            ->addColumn('amount', function ($discount) {
                if ($discount->type === 'Fixed Amount') {
                    // Jika tipe fixed amount, tampilkan dalam format rupiah
                    return 'Rp ' . number_format($discount->amount, 0, ',', '.');
                } elseif ($discount->type === 'Percentage') {
                    // Jika tipe persentase, tambahkan tanda %
                    return number_format($discount->amount, 0, ',', '.') . ' %';
                } else {
                    return '-';
                }
            })
            ->addColumn('minimum_based_on', function ($discount) {
                return $discount->minimum_based_on;
            })
            ->addColumn('minimum_qty_or_amount', function ($discount) {
                if ($discount->minimum_based_on === 'Quantity of Items') {
                    // Jika berdasarkan jumlah item
                    return number_format($discount->minimum_qty_or_amount, 0, ',', '.');
                } elseif ($discount->minimum_based_on === 'Purchase Amount') {
                    // Jika berdasarkan total pembelian
                    return 'Rp ' . number_format($discount->minimum_qty_or_amount, 0, ',', '.');
                } else {
                    return '-';
                }
            })
            ->addColumn('apply_on', function ($discount) {
                $label = match ($discount->apply_on) {
                    'Product' => 'Product',
                    'Category' => 'Product Category',
                    'Mode' => 'Mode',
                    default => null,
                };

                if (!$label) {
                    return '-';
                }

                $targets = match ($discount->apply_on) {
                    'Product' => $discount->products->pluck('name'),
                    'Category' => $discount->categories->pluck('name'),
                    'Mode' => $discount->priceModes->pluck('name'),
                };

                if ($targets->isEmpty()) {
                    return e($label);
                }

                return e($label) . '<div class="fs-11 text-muted">' . e($targets->implode(', ')) . '</div>';
            })
            ->addColumn('start_date', function ($discount) {
                return $discount->start_date;
            })
            ->addColumn('end_date', function ($discount) {
                return $discount->end_date;
            })
            ->addColumn('is_active', function ($discount) {
                if ($discount->is_active) {
                    return '<span class="badge bg-soft-success text-success">Active</span>';
                } else {
                    return '<span class="badge bg-soft-danger text-danger">Inactive</span>';
                }
            })
            ->addColumn('action', function ($discount) {
                return view('erp.pages.discounts.partials.action-button', compact('discount'))->render();
            })
            ->rawColumns(['action', 'is_active', 'apply_on'])
            ->make(true);
    }

    public function create()
    {
        $products = Products::all();
        $categories = ProductCategory::all();
        $priceModes = PriceMode::active()->ordered()->get();
        return view('erp.pages.discounts.create-discount', compact('products', 'categories', 'priceModes'));
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Percentage,Fixed Amount',
            'amount' => 'required|numeric|min:0',
            'minimum_based_on' => 'required|in:Quantity of Items,Purchase Amount',
            'minimum_qty_or_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'apply_on' => 'required|in:Product,Category,Mode',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:product_categories,id',
            'price_modes' => 'required_if:apply_on,Mode|nullable|array',
            'price_modes.*' => 'exists:price_modes,id',
            'status' => 'required|in:1,0',
        ]);

        try {
            DB::beginTransaction();

            $discount = Discount::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'minimum_based_on' => $validated['minimum_based_on'],
                'minimum_qty_or_amount' => $validated['minimum_qty_or_amount'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'apply_on' => $validated['apply_on'],
                'is_active' => $validated['status'],
            ]);

            // 3. UPDATE RELASI ERP
            if ($validated['apply_on'] === 'Product') {
                $discount->products()->sync($validated['products'] ?? []);
            } elseif ($validated['apply_on'] === 'Category') {
                $discount->categories()->sync($validated['categories'] ?? []);
            } elseif ($validated['apply_on'] === 'Mode') {
                $discount->priceModes()->sync($validated['price_modes'] ?? []);
            }

            DB::commit();

            return redirect('/erp/discounts')->with('success', 'Discount created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create discount. ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $discount = Discount::with(['products', 'categories', 'priceModes'])->where('id', $id)->first();
        $products = Products::all();
        $categories = ProductCategory::all();
        $priceModes = PriceMode::active()->ordered()->get();
        return view('erp.pages.discounts.edit-discount', compact('discount', 'products', 'categories', 'priceModes'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Percentage,Fixed Amount',
            'amount' => 'required|numeric|min:0',
            'minimum_based_on' => 'required|in:Quantity of Items,Purchase Amount',
            'minimum_qty_or_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'apply_on' => 'required|in:Product,Category,Mode',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:product_categories,id',
            'price_modes' => 'required_if:apply_on,Mode|nullable|array',
            'price_modes.*' => 'exists:price_modes,id',
            'status' => 'required|in:1,0',
        ]);

        try {
            DB::beginTransaction();

            $discount = Discount::with(['products', 'categories', 'priceModes'])->findOrFail($id);

            // 2. UPDATE DATA DISCOUNT
            $discount->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'minimum_based_on' => $validated['minimum_based_on'],
                'minimum_qty_or_amount' => $validated['minimum_qty_or_amount'],
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'apply_on' => $validated['apply_on'],
                'is_active' => $validated['status'],
            ]);

            // 3. UPDATE RELASI ERP
            if ($validated['apply_on'] === 'Product') {
                $discount->products()->sync($validated['products'] ?? []);
                $discount->categories()->detach();
                $discount->priceModes()->detach();
            } elseif ($validated['apply_on'] === 'Category') {
                $discount->categories()->sync($validated['categories'] ?? []);
                $discount->products()->detach();
                $discount->priceModes()->detach();
            } elseif ($validated['apply_on'] === 'Mode') {
                $discount->priceModes()->sync($validated['price_modes'] ?? []);
                $discount->products()->detach();
                $discount->categories()->detach();
            }

            DB::commit();
            return redirect('/erp/discounts')->with('success', 'Discount updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Discount update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update discount. ' . $e->getMessage());
        }
    }

    private function calculateSalePrice($originalPrice, $type, $amount)
    {
        if ($type === 'Percentage') {
            return max(0, $originalPrice - ($originalPrice * ($amount / 100)));
        } else { // Fixed Amount
            return max(0, $originalPrice - $amount);
        }
    }

    public function delete($id)
    {
        $discount = Discount::where('id', $id)->first();

        if ($discount) {
            // Hapus relasi pivot dulu
            $discount->products()->detach();
            $discount->categories()->detach();
            $discount->priceModes()->detach();

            // Lalu hapus discount-nya
            $discount->delete();

            return redirect('/erp/discounts')->with('success', 'Discount deleted successfully!');
        }

        return redirect('/erp/discounts')->with('error', 'Discount not found.');
    }
}
