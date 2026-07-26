<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\CustomerCartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerCartController extends Controller
{
    public function index(Request $request)
    {
        return $this->cartResponse($request);
    }

    public function sync(Request $request)
    {
        $validated = $request->validate([
            'items' => ['present', 'array', 'max:100'],
            'items.*.cart_item_key' => ['required', 'string', 'max:191', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'items.*.is_selected' => ['required', 'boolean'],
            'items.*.item_data' => ['required', 'array'],
        ]);

        $account = $request->user();
        $items = collect($validated['items']);
        $keys = $items->pluck('cart_item_key')->all();

        DB::transaction(function () use ($account, $items, $keys) {
            $query = CustomerCartItem::query()
                ->where('customer_account_id', $account->id);

            if (empty($keys)) {
                $query->delete();
            } else {
                $query->whereNotIn('cart_item_key', $keys)->delete();
            }

            foreach ($items as $item) {
                CustomerCartItem::updateOrCreate(
                    [
                        'customer_account_id' => $account->id,
                        'cart_item_key' => $item['cart_item_key'],
                    ],
                    [
                        'quantity' => $item['quantity'],
                        'is_selected' => $item['is_selected'],
                        'item_data' => $item['item_data'],
                    ]
                );
            }
        });

        return $this->cartResponse($request);
    }

    private function cartResponse(Request $request)
    {
        $account = $request->user();
        $items = CustomerCartItem::query()
            ->where('customer_account_id', $account->id)
            ->orderBy('id')
            ->get()
            ->map(function (CustomerCartItem $cartItem) {
                return array_merge($cartItem->item_data, [
                    'id' => $cartItem->cart_item_key,
                    'quantity' => $cartItem->quantity,
                    'isSelected' => $cartItem->is_selected,
                ]);
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'items' => $items,
            ],
        ]);
    }
}
