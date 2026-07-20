{{-- <div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
        $isCompleted = $inventory->items->every(fn($item) => $item->stock_in >= ($item->qty_base ?? $item->quantity));
        @endphp

        @if (!$isCompleted)
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
</div> --}}


<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">

        @if (!$isCompleted && !empty($inventoryId))
            <li>
                <a class="dropdown-item"
                    href="/erp/inventory/stock-in/by-pl/{{ $inventoryId }}/add">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Stock In</span>
                </a>
            </li>
        @elseif (!$isCompleted && $supplierId)
            <li>
                <a class="dropdown-item"
                    href="/erp/inventory/stock-in/add-stock-in/{{ $supplierId }}/{{ $year }}/{{ $month }}">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Stock In</span>
                </a>
            </li>
        @endif

        <li>
            <a class="dropdown-item" href="{{ !empty($inventoryId)
                ? url('/erp/inventory/stock-in/by-pl/' . $inventoryId . '/history')
                : url('/erp/inventory/stock-in/history/' . $supplierId . '/' . $year . '/' . $month) }}">
                <i class="feather feather-info me-3"></i>
                <span>History Stock In</span>
            </a>
        </li>

    </ul>
</div>
