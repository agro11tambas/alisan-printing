<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
        $hasIssuedQty = $materialRequest->items->sum('issued_qty') > 0;
        $isFullyIssued = $materialRequest->items->every(function($item) {
        return $item->issued_qty >= $item->requested_qty;
        });
        $hasReceivedQty = $materialRequest->items->sum('received_qty') > 0;
        @endphp

        {{-- ✅ Tampilkan Verified kalau sudah ada issued qty --}}
        @if ($hasIssuedQty && !$hasReceivedQty)
        <li>
            <button type="button"
                class="dropdown-item btn-verify"
                data-bs-toggle="modal"
                data-bs-target="#modalChangeStatus"
                data-id="{{ $materialRequest->id }}"
                data-name="{{ $materialRequest->name }}"
                data-url="{{ url('/erp/productions/material-request/mark-as-verified/' . $materialRequest->id) }}">
                <i class="feather feather-check me-3"></i>
                <span>Verified</span>
            </button>
        </li>
        @endif

        {{-- ✅ Edit hanya kalau belum fully issued & belum received --}}
        @if (!$isFullyIssued && !$hasReceivedQty)
        <li>
            <a class="dropdown-item" href="/erp/productions/material-request/edit/{{ $materialRequest->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        @endif

        {{-- ✅ Delete hanya kalau belum received --}}
        @if (!$hasReceivedQty && !$hasIssuedQty)
        <li>
            <button type="button"
                class="dropdown-item btn-delete"
                data-bs-toggle="modal"
                data-bs-target="#modalDeleteRequestStock"
                data-id="{{ $materialRequest->id }}"
                data-name="{{ $materialRequest->name }}"
                data-url="{{ url('/erp/productions/material-request/delete/' . $materialRequest->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
        @endif
    </ul>
</div>