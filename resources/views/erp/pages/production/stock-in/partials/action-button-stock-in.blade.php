{{-- <div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
            $isCompleted = $inventory->items->every(fn($item) => $item->stock_in >= ($item->qty_base ?? $item->quantity));
        @endphp

        @if (!$isCompleted)
            <li>
                <a class="dropdown-item" href="/erp/productions/stock-in/add-stock-in/{{ $inventory->id }}">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Stock In</span>
                </a>
            </li>
        @endif
        <li>
            <a class="dropdown-item" href="/erp/productions/stock-in/history/{{ $inventory->id }}">
                <i class="feather feather-info me-3"></i>
                <span>History Stock In</span>
            </a>
        </li>
    </ul>
</div> --}}

{{-- <div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">

        @if (!$isCompleted && $supplierId)
            <li>
                <a class="dropdown-item"
                    href="/erp/productions/stock-in/add-stock-in/{{ $supplierId }}/{{ $year }}/{{ $month }}">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Stock In</span>
                </a>
            </li>
        @endif

        <li>
            <a class="dropdown-item"
                href="/erp/productions/stock-in/history/{{ $supplierId }}/{{ $year }}/{{ $month }}">
                <i class="feather feather-info me-3"></i>
                <span>History Stock In</span>
            </a>
        </li>

    </ul>
</div> --}}


<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">

        @if (!$isCompleted && !empty($purchaseOrderId))
            <li>
                <a class="dropdown-item" href="/erp/productions/stock-in/by-po/{{ $purchaseOrderId }}/add">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Stock In</span>
                </a>
            </li>
        @elseif (!$isCompleted && !empty($inventoryId))
            <li>
                <a class="dropdown-item" href="/erp/productions/stock-in/by-pl/{{ $inventoryId }}/add">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Stock In</span>
                </a>
            </li>
        @elseif (!$isCompleted && $supplierId)
            <li>
                <a class="dropdown-item" href="/erp/productions/stock-in/add-stock-in/{{ $supplierId }}">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Stock In</span>
                </a>
            </li>
        @endif

        <li>
            <a class="dropdown-item" href="{{ !empty($purchaseOrderId)
                ? url('/erp/productions/stock-in/by-po/' . $purchaseOrderId . '/history')
                : (!empty($inventoryId)
                    ? url('/erp/productions/stock-in/by-pl/' . $inventoryId . '/history')
                    : url('/erp/productions/stock-in/history/' . $supplierId)) }}">
                <i class="feather feather-info me-3"></i>
                <span>History Stock In</span>
            </a>
        </li>

    </ul>
</div>
