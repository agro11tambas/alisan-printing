<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\PriceMode;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

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
        ]);

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
                    return 'Rp '.number_format($discount->amount, 0, ',', '.');
                } elseif ($discount->type === 'Percentage') {
                    // Jika tipe persentase, tambahkan tanda %
                    return number_format($discount->amount, 0, ',', '.').' %';
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
                    return 'Rp '.number_format($discount->minimum_qty_or_amount, 0, ',', '.');
                } else {
                    return '-';
                }
            })
            ->addColumn('apply_on', function ($discount) {
                $scopes = $discount->apply_on_list;

                if (empty($scopes)) {
                    return '-';
                }

                // Scope-nya bisa lebih dari satu dan semuanya harus terpenuhi,
                // jadi tiap scope ditampilkan sebaris beserta targetnya.
                $rows = collect($scopes)->map(function ($scope) use ($discount) {
                    [$label, $targets] = match ($scope) {
                        // "Product" hanya muncul dari data lama; sudah tidak bisa dipilih lagi.
                        'Product' => ['Product', $discount->products->pluck('name')],
                        'Category' => ['Product Category', $discount->categories->pluck('name')],
                        'Mode' => ['Mode', $discount->priceModes->pluck('name')],
                        default => [$scope, collect()],
                    };

                    $html = '<div>'.e($label).'</div>';

                    if ($targets->isNotEmpty()) {
                        $html .= '<div class="fs-11 text-muted">'.e($targets->implode(', ')).'</div>';
                    }

                    return $html;
                });

                $prefix = count($scopes) > 1
                    ? '<div class="fs-11 text-muted fst-italic">Semua syarat harus terpenuhi</div>'
                    : '';

                return $prefix.$rows->implode('<div class="my-1"></div>');
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
        return view('erp.pages.discounts.create-discount', $this->formOptions());
    }

    public function store(Request $request)
    {
        $validated = $this->validateDiscount($request);

        try {
            DB::beginTransaction();

            $discount = Discount::create($this->discountAttributes($validated));
            $this->syncTargets($discount, $validated);

            DB::commit();

            return redirect('/erp/discounts')->with('success', 'Discount created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to create discount. '.$e->getMessage());
        }
    }

    public function edit($id)
    {
        $discount = Discount::with(['categories', 'priceModes'])
            ->where('id', $id)
            ->first();

        return view('erp.pages.discounts.edit-discount', array_merge(
            $this->formOptions(),
            ['discount' => $discount]
        ));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validateDiscount($request);

        try {
            DB::beginTransaction();

            $discount = Discount::findOrFail($id);
            $discount->update($this->discountAttributes($validated));
            $this->syncTargets($discount, $validated);

            DB::commit();

            return redirect('/erp/discounts')->with('success', 'Discount updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Discount update failed: '.$e->getMessage());

            return redirect()->back()->with('error', 'Failed to update discount. '.$e->getMessage());
        }
    }

    /**
     * Pilihan yang dipakai bersama oleh form create dan edit.
     */
    private function formOptions(): array
    {
        return [
            'categories' => ProductCategory::all(),
            'priceModes' => PriceMode::active()->ordered()->get(),
        ];
    }

    /**
     * Aturan yang sama untuk create dan update.
     *
     * `apply_on` sekarang daftar: boleh lebih dari satu scope, dan tiap scope
     * yang dipilih wajib punya minimal satu target — kalau tidak, diskonnya
     * tidak akan pernah kena baris mana pun karena syaratnya digabung AND.
     */
    private function validateDiscount(Request $request): array
    {
        // Toleran terhadap kiriman lama yang masih mengirim satu nilai string.
        $request->merge([
            'apply_on' => array_values(array_filter((array) $request->input('apply_on', []))),
        ]);

        $validator = validator($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:Percentage,Fixed Amount',
            'amount' => 'required|numeric|min:0',
            'minimum_based_on' => 'required|in:Quantity of Items,Purchase Amount',
            'minimum_qty_or_amount' => 'required|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'apply_on' => 'required|array|min:1',
            'apply_on.*' => 'in:'.implode(',', Discount::SCOPES),
            'categories' => 'nullable|array',
            'categories.*' => 'exists:product_categories,id',
            'price_modes' => 'nullable|array',
            'price_modes.*' => 'exists:price_modes,id',
            'status' => 'required|in:1,0',
        ]);

        $validator->after(function ($validator) use ($request) {
            $scopes = Discount::parseScopes($request->input('apply_on', []), Discount::SCOPES);

            $required = [
                'Category' => ['categories', 'Product Category wajib dipilih minimal satu'],
                'Mode' => ['price_modes', 'Mode wajib dipilih minimal satu'],
            ];

            foreach ($scopes as $scope) {
                [$field, $message] = $required[$scope];

                if (empty(array_filter((array) $request->input($field, [])))) {
                    $validator->errors()->add($field, $message);
                }
            }
        });

        return $validator->validate();
    }

    private function discountAttributes(array $validated): array
    {
        $scopes = Discount::parseScopes($validated['apply_on'], Discount::SCOPES);

        return [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'minimum_based_on' => $validated['minimum_based_on'],
            'minimum_qty_or_amount' => $validated['minimum_qty_or_amount'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'apply_on' => implode(',', $scopes),
            'is_active' => $validated['status'],
        ];
    }

    /**
     * Isi pivot untuk scope yang dipilih, kosongkan pivot untuk yang tidak.
     *
     * Pivot produk ikut dikosongkan: scope "Product" sudah tidak ada di form,
     * jadi diskon lama yang memakainya kehilangan targetnya begitu disimpan ulang.
     */
    private function syncTargets(Discount $discount, array $validated): void
    {
        $scopes = Discount::parseScopes($validated['apply_on'], Discount::SCOPES);

        $map = [
            'Product' => ['products', 'products'],
            'Category' => ['categories', 'categories'],
            'Mode' => ['priceModes', 'price_modes'],
        ];

        foreach ($map as $scope => [$relation, $field]) {
            $ids = in_array($scope, $scopes, true)
                ? array_values(array_filter((array) ($validated[$field] ?? [])))
                : [];

            $discount->{$relation}()->sync($ids);
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
            $discount->ecommerceCategories()->detach();

            // Lalu hapus discount-nya
            $discount->delete();

            return redirect('/erp/discounts')->with('success', 'Discount deleted successfully!');
        }

        return redirect('/erp/discounts')->with('error', 'Discount not found.');
    }
}
