<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
            // completed = semua item stock_out >= quantity
            $isCompleted = $inventory->items->every(fn($item) => $item->stock_out >= $item->quantity);
        @endphp

        @if (! $isCompleted)
        <li>
            <a class="dropdown-item" href="/erp/inventory/stock-out/add-stock-out/{{ $inventory->id }}">
                <i class="feather feather-plus me-3"></i>
                <span>Add Stock Out</span>
            </a>
        </li>
        @endif
        <!-- <li>
            <a class="dropdown-item" href="/erp/inventory/stock-out/edit-stock-out/{{ $inventory->id }}">
                <i class="feather feather-edit me-3"></i>
                <span>Edit Stock Out</span>
            </a>
        </li> -->
        <li>
            <a class="dropdown-item" href="/erp/inventory/stock-out/history/{{ $inventory->id }}">
                <i class="feather feather-info me-3"></i>
                <span>History Stock Out</span>
            </a>
        </li>
    </ul>
</div>