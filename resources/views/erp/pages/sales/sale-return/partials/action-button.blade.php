<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <div class="action-grid">
            <div class="action-col">
                @php
                    // Cek apakah masih ada canceled product yang belum di-return semua
                    $hasRemainingCanceled = $hasRemainingCanceled ?? false;
                    // Status sudah dihitung dari relasi yang di-eager-load controller.
                    // Tidak ada query database saat partial ini dirender.
                @endphp

                @if ($hasRemainingCanceled)
                    <li>
                        <button type="button" class="dropdown-item btn-return-warehouse" data-bs-toggle="modal"
                            data-bs-target="#modalReturnToWarehouse" data-id="{{ $return->id }}"
                            data-url="{{ url('/erp/sales/sale-returns/get-canceled-products/' . $return->id) }}">
                            <i class="feather feather-package me-3"></i>
                            <span>Return to Warehouse</span>
                        </button>
                    </li>
                @endif

                <li>
                    <hr class="my-1">
                </li>

                @php
                    $hidePaymentActions = in_array($return->payment_status, ['Retur', 'Refunded', 'Customer Deposit']);

                    $canShowPaymentActions = !$hidePaymentActions && $return->remaining_amount > 0;
                @endphp

                {{-- @if ($return->payment_status !== 'Retur' && $return->payment_status !== 'Refunded' && $return->remaining_amount > 0)
                    <li>
                        <button type="button" class="dropdown-item btn-mark-retur" data-id="{{ $return->id }}"
                            data-order-number="{{ $return->order_number }}"
                            data-url="{{ url('/erp/sales/sale-returns/mark-as-retur/' . $return->id) }}">
                            <i class="feather feather-corner-down-left me-3"></i>
                            <span>Mark as Retur</span>
                        </button>
                    </li>
                @endif

                @if ($return->saleOrder && $return->saleOrder->payment_status !== 'Retur' && $return->payment_status !== 'Refunded' && $return->remaining_amount > 0)
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

                @if ($return->saleOrder && $return->saleOrder->payment_status !== 'Retur' && $return->payment_status !== 'Refunded' && $return->remaining_amount > 0)
                    <li>
                        <button type="button" class="dropdown-item btn-mark-deposit" data-bs-toggle="modal"
                            data-bs-target="#modalMarkAsCustomerDeposit" data-id="{{ $return->id }}"
                            data-remaining="{{ $return->remaining_amount }}"
                            data-url="{{ url('/erp/sales/sale-returns/mark-as-customer-deposit/' . $return->id) }}">
                            <i class="feather feather-credit-card me-3"></i>
                            <span>Mark as Customer Deposit</span>
                        </button>
                    </li>
                @endif --}}

                @if ($canShowPaymentActions)
                    <li>
                        <button type="button" class="dropdown-item btn-mark-retur" data-id="{{ $return->id }}"
                            data-order-number="{{ $return->order_number }}"
                            data-url="{{ url('/erp/sales/sale-returns/mark-as-retur/' . $return->id) }}">
                            <i class="feather feather-corner-down-left me-3"></i>
                            <span>Mark as Retur</span>
                        </button>
                    </li>
                @endif

                @if ($return->saleOrder && $return->saleOrder->payment_status !== 'Retur' && $canShowPaymentActions)
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

                @if ($return->saleOrder && $return->saleOrder->payment_status !== 'Retur' && $canShowPaymentActions)
                    <li>
                        <button type="button" class="dropdown-item btn-mark-deposit" data-bs-toggle="modal"
                            data-bs-target="#modalMarkAsCustomerDeposit" data-id="{{ $return->id }}"
                            data-remaining="{{ $return->remaining_amount }}"
                            data-url="{{ url('/erp/sales/sale-returns/mark-as-customer-deposit/' . $return->id) }}">
                            <i class="feather feather-credit-card me-3"></i>
                            <span>Mark as Customer Deposit</span>
                        </button>
                    </li>
                @endif

                <li>
                    <hr class="my-1">
                </li>

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
            <hr class="my-1">
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
                @if (!$hasStockIn)
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
