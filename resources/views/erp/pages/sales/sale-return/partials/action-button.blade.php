<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <div class="action-grid">
            <div class="action-col">
                @if ($return->payment_status !== 'Paid')
                    <li>
                        <button type="button" class="dropdown-item btn-mark-paid" data-bs-toggle="modal"
                            data-bs-target="#modalChangeStatus" data-id="{{ $return->id }}"
                            data-paid="{{ $return->refund_amount }}" data-total-amount="{{ $return->total_amount }}"
                            data-paid-amount="{{ $return->refund_amount }}"
                            data-url="{{ url('/erp/sales/sale-returns/mark-as-refund/' . $return->id) }}">
                            <i class="feather feather-check"></i>
                            <span>Mark as Refund</span>
                        </button>
                    </li>
                @endif
                <li>
                    <a href="/erp/sales/sale-return/payment-history/{{ $return->id }}" class="dropdown-item">
                        <i class="feather feather-dollar-sign me-3"></i>
                        <span>Payment History</span>
                    </a>
                </li>
                <li>
                    <a href="/erp/sales/sale-returns/detail-order/{{ $return->id }}" class="dropdown-item">
                        <i class="feather feather-eye"></i>
                        <span>Order Detail</span>
                    </a>
                </li>
            </div>
            {{-- <li>
            <hr class="my-2">
                </li> --}}


            <div class="action-col">
                <li>
                    <a class="dropdown-item" href="/erp/sales/sale-returns/edit-sale-return/{{ $return->id }}">
                        <i class="feather feather-edit-3 me-3"></i>
                        <span>Edit</span>
                    </a>
                </li>
                <li>
                    <a href="/erp/sales/sale-return/edit-history/{{ $return->id }}" class="dropdown-item">
                        <i class="feather feather-clock me-3"></i>
                        <span>Edit History</span>
                    </a>
                </li>
                @if (!$return->hasStockIn())
                    <li>
                        <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                            data-bs-target="#modalDeleteOrder" data-id="{{ $return->id }}"
                            data-name="{{ $return->name }}"
                            data-url="{{ url('/erp/sales/sale-returns/delete/' . $return->id) }}">
                            <i class="feather feather-trash-2 me-3"></i>
                            <span>Delete</span>
                        </button>
                    </li>
                @endif

                {{-- @if (Auth::check() && Auth::user()->role === 'Owner')
            <li>
                <button type="button" class="dropdown-item text-danger fw-bold btn-force-delete-owner"
                    data-bs-toggle="modal" data-bs-target="#modalForceDeleteOwner"
                    data-url="{{ url('/erp/sales/sale-returns/force-delete-owner/' . $return->id) }}"
                    data-name="{{ $return->order_number }}">
                    <i class="feather feather-x-circle me-3 text-danger"></i>
                    <span>Force Delete (Owner)</span>
                </button>
            </li>
        @endif --}}
            </div>
        </div>

    </ul>
</div>
