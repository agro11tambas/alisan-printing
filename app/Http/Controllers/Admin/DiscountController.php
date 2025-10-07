<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Discount;
use App\Models\ProductBundle;
use App\Models\ProductBundleItem;
use App\Models\Products;
use App\Models\ProductCategory;
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
        $discount = Discount::all();

        return DataTables::of($discount)
            ->addIndexColumn()
            ->addColumn('name', function ($discount) {
                return $discount->name;
            })
            ->addColumn('type', function ($discount) {
                return $discount->type;
            })
            ->addColumn('amount', function ($discount) {
                return $discount->amount;
            })
            ->addColumn('minimum_based_on', function ($discount) {
                return $discount->minimum_based_on;
            })
            ->addColumn('minimum_qty_or_amount', function ($discount) {
                return $discount->minimum_qty_or_amount;
            })
            ->addColumn('start_date', function ($discount) {
                return $discount->start_date;
            })
            ->addColumn('end_date', function ($discount) {
                return $discount->end_date;
            })
            ->addColumn('is_active', function ($discount) {
                return $discount->is_active;
            })
            ->addColumn('action', function ($discount) {
                return view('erp.pages.discounts.partials.action-button', compact('discount'))->render();
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create()
    {
        $products = Products::all();
        $categories = ProductCategory::all();
        return view('erp.pages.discounts.create-discount', compact('products', 'categories'));
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
            'apply_on' => 'required|in:Product,Category',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:product_categories,id',
            // 'start_date' => 'required|date',
            // 'end_date' => 'required|date|after_or_equal:start_date',
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
                'apply_on' => $validated['apply_on'],
                // 'start_date' => $validated['start_date'],
                // 'end_date' => $validated['end_date'],
                'is_active' => $validated['status'],
            ]);

            if ($validated['apply_on'] === 'Product' && isset($validated['products'])) {
                $discount->products()->sync($validated['products']);

                // Update sale_price untuk produk yang dipilih
                foreach ($validated['products'] as $productId) {
                    $product = Products::find($productId);

                    if ($product) {
                        $newPrice = $product->price;
                        if ($validated['type'] === 'Percentage') {
                            $newPrice -= ($product->price * ($validated['amount'] / 100));
                        } else { // Fixed Amount
                            $newPrice -= $validated['amount'];
                        }

                        // Pastikan harga tidak negatif
                        $newPrice = max(0, $newPrice);

                        $product->update(['sale_price' => $newPrice]);
                    }
                }
            }

            if ($validated['apply_on'] === 'Category' && !empty($validated['categories'])) {
                $discount->categories()->sync($validated['categories']);

                // Ambil semua produk di kategori ini
                $products = Products::whereHas('categories', function ($q) use ($validated) {
                    $q->whereIn('product_categories.id', $validated['categories']);
                })->get();

                // Update harga produk dulu
                foreach ($products as $product) {
                    $newPrice = $this->calculateSalePrice(
                        $product->price,
                        $validated['type'],
                        $validated['amount']
                    );
                    $product->update(['sale_price' => max(0, $newPrice)]);

                    // Update sale price bundle yang berisi produk ini
                    $bundleIds = $product->includedInBundles()->pluck('bundle_id')->unique();
                    foreach ($bundleIds as $bundleId) {
                        $bundle = ProductBundle::find($bundleId);
                        if ($bundle) {
                            $newBundlePrice = $this->calculateSalePrice(
                                $bundle->price, // harga bundle asli
                                $validated['type'],
                                $validated['amount']
                            );
                            $bundle->update(['sale_price' => max(0, $newBundlePrice)]);
                        }
                    }
                }
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
        $discount = Discount::where('id', $id)->first();
        $products = Products::all();
        $categories = ProductCategory::all();
        return view('erp.pages.discounts.edit-discount', compact('discount', 'products', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:Percentage,Fixed Amount',
            'amount' => 'required|numeric|min:0',
            'minimum_based_on' => 'required|in:Quantity of Items,Purchase Amount',
            'minimum_qty_or_amount' => 'required|numeric|min:0',
            'apply_on' => 'required|in:Product,Category',
            'products' => 'nullable|array',
            'products.*' => 'exists:products,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:product_categories,id',
            // 'start_date' => 'required|date',
            // 'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:1,0',
        ]);

        try {
            DB::beginTransaction();

            $discount = Discount::with(['products', 'categories'])->findOrFail($id);

            // 1. RESET SALE PRICE PADA PRODUK LAMA
            foreach ($discount->products as $oldProduct) {
                $oldProduct->update(['sale_price' => $oldProduct->price]);
            }
            foreach ($discount->categories as $oldCategory) {
                $products = Products::whereHas('categories', function ($q) use ($oldCategory) {
                    $q->where('product_categories.id', $oldCategory->id);
                })->get();

                foreach ($products as $p) {
                    $p->update(['sale_price' => $p->price]);
                }
            }

            // 2. UPDATE DATA DISCOUNT
            $discount->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'minimum_based_on' => $validated['minimum_based_on'],
                'minimum_qty_or_amount' => $validated['minimum_qty_or_amount'],
                'apply_on' => $validated['apply_on'],
                // 'start_date' => $validated['start_date'],
                // 'end_date' => $validated['end_date'],
                'is_active' => $validated['status'],
            ]);

            // 3. UPDATE RELASI DAN SALE_PRICE BARU
            if ($validated['apply_on'] === 'Product') {
                $discount->products()->sync($validated['products'] ?? []);
                $discount->categories()->detach();

                if (!empty($validated['products'])) {
                    foreach ($validated['products'] as $productId) {
                        $product = Products::find($productId);
                        if ($product) {
                            $newPrice = $this->calculateSalePrice($product->price, $validated['type'], $validated['amount']);
                            $product->update(['sale_price' => $newPrice]);
                        }
                    }
                }
            } elseif ($validated['apply_on'] === 'Category') {
                $discount->categories()->sync($validated['categories'] ?? []);
                $discount->products()->detach();

                if (!empty($validated['categories'])) {
                    $products = Products::whereHas('categories', function ($q) use ($validated) {
                        $q->whereIn('product_categories.id', $validated['categories']);
                    })->get();

                    foreach ($products as $product) {
                        // Update harga produk
                        $newPrice = $this->calculateSalePrice(
                            $product->price,
                            $validated['type'],
                            $validated['amount']
                        );
                        $product->update(['sale_price' => max(0, $newPrice)]);

                        // Update harga bundle yang mengandung produk ini
                        $bundleIds = $product->includedInBundles()->pluck('bundle_id')->unique();
                        foreach ($bundleIds as $bundleId) {
                            $bundle = ProductBundle::find($bundleId);
                            if ($bundle) {
                                $newBundlePrice = $this->calculateSalePrice(
                                    $bundle->price,
                                    $validated['type'],
                                    $validated['amount']
                                );
                                $bundle->update(['sale_price' => max(0, $newBundlePrice)]);
                            }
                        }
                    }
                }
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

            // Lalu hapus discount-nya
            $discount->delete();

            return redirect('/erp/discounts')->with('success', 'Discount deleted successfully!');
        }

        return redirect('/erp/discounts')->with('error', 'Discount not found.');
    }
}
