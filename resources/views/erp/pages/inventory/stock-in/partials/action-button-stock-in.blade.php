<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
        $isCompleted = $inventory->items->every(fn($item) => $item->stock_in >= $item->quantity);
        @endphp

        @if (! $isCompleted)
        <li>
            <a class="dropdown-item" href="/erp/inventory/stock-in/add-stock-in/{{ $inventory->id }}">
                <i class="feather feather-plus me-3"></i>
                <span>Add Stock In</span>
            </a>
        </li>
        @endif
        <li>
            <a class="dropdown-item" href="/erp/inventory/stock-in/history/{{ $inventory->id }}">
                <i class="feather feather-info me-3"></i>
                <span>History Stock In</span>
            </a>
        </li>
    </ul>
</div>