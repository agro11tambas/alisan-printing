<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @php
            $isFullyIssued = $materialRequest->items->every(function ($item) {
                return $item->issued_qty >= $item->requested_qty;
            });

            $isFullyReceived = $materialRequest->items->every(function ($item) {
                return $item->received_qty >= $item->requested_qty;
            });

            $isEditable = $materialRequest->items->every(function ($item) {
                return $item->requested_qty >= 0;
            });

            $hasIssued = $materialRequest->items->sum('issued_qty') > 0;
            $hasReceived = $materialRequest->items->sum('received_qty') > 0;
        @endphp
        @if ($hasIssued && !$isFullyReceived)
            <li>
                <button type="button" class="dropdown-item btn-verify" data-bs-toggle="modal"
                    data-bs-target="#modalChangeStatus" data-id="{{ $materialRequest->id }}"
                    data-name="{{ $materialRequest->name }}"
                    data-url="{{ url('/erp/productions/material-request/mark-as-verified/' . $materialRequest->id) }}">
                    <i class="feather feather-check me-3"></i>
                    <span>Verified</span>
                </button>
            </li>
        @endif

        @if ($isEditable && !$hasIssued && !$hasReceived)
            <li>
                <a class="dropdown-item" href="/erp/productions/material-request/edit/{{ $materialRequest->id }}">
                    <i class="feather feather-edit-3 me-3"></i>
                    <span>Edit</span>
                </a>
            </li>
        @endif

        @if ($isFullyIssued || $isFullyReceived)
            <li>
                <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                    data-bs-target="#modalDeleteRequestStock" data-id="{{ $materialRequest->id }}"
                    data-name="{{ $materialRequest->name }}"
                    data-url="{{ url('/erp/productions/material-request/delete/' . $materialRequest->id) }}">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Delete</span>
                </button>
            </li>
        @endif
    </ul>
</div>
