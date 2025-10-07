<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
        // completed = semua item stock_in >= quantity
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
        <!-- <li>
            <a class="dropdown-item" href="/erp/inventory/stock-in/edit-stock-in/{{ $inventory->id }}">
                <i class="feather feather-edit me-3"></i>
                <span>Edit Stock Out</span>
            </a>
        </li> -->
        <li>
            <a class="dropdown-item" href="/erp/inventory/stock-in/history/{{ $inventory->id }}">
                <i class="feather feather-info me-3"></i>
                <span>History Stock In</span>
            </a>
        </li>
    </ul>
</div>