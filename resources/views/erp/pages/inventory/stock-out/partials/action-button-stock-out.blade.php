<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
            $isCompleted = $inventory->items->every(fn($item) => $item->stock_out >= $item->quantity);
        @endphp

        @if (!$isCompleted)
            <li>
                <a href="javascript:void(0)" class="dropdown-item btn-open-stockout-modal" data-id="{{ $inventory->id }}"
                    data-number="{{ $inventory->order_number ?? $inventory->purchase_number }}">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Stock Out</span>
                </a>
            </li>
        @endif
        <li>
            <a class="dropdown-item" href="/erp/inventory/stock-out/history/{{ $inventory->id }}">
                <i class="feather feather-info me-3"></i>
                <span>History Stock Out</span>
            </a>
        </li>
    </ul>
</div>
