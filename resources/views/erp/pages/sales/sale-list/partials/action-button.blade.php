<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <div class="action-grid">
            <div class="action-col">
                <span class="action-title">Invoice</span>
                <li>
                    <button type="button" class="dropdown-item btn-share-invoice-image" data-id="{{ $order->id }}"
                        data-url="{{ url('/erp/sales/sale-list/invoice/' . $order->id) }}"
                        data-customer="{{ $order->customer->name }}">
                        <i class="feather feather-share-2 me-3"></i>
                        <span>Share Invoice</span>
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item btn-share-invoice" data-id="{{ $order->id }}"
                        data-url="{{ url('/erp/sales/sale-list/invoice/' . $order->id) }}">
                        <i class="feather feather-file-text me-3"></i>
                        <span>Invoice</span>
                    </button>
                </li>
                <li>
                    <a href="/erp/sales/sale-list/detail-order/{{ $order->id }}" class="dropdown-item">
                        <i class="feather feather-eye me-3"></i>
                        <span>Order Detail</span>
                    </a>
                </li>

                @if (!$order->is_fully_returned && $order->has_delivery_list)
                    <li>
                        <a href="/erp/sales/sale-returns/create-sale-return/{{ $order->id }}" class="dropdown-item">
                            <i class="feather feather-corner-down-left me-3"></i>
                            <span>Make Sale Return</span>
                        </a>
                    </li>
                @endif
            </div>
            <div class="action-col">
                <span class="action-title">Payment</span>
                @if ($order->payment_status !== 'Paid' && $order->payment_status !== 'Overpaid')
                    <li>
                        <button type="button" class="dropdown-item btn-mark-paid" data-bs-toggle="modal"
                            data-bs-target="#modalChangeStatus" data-id="{{ $order->id }}"
                            data-paid="{{ $order->paid_amount }}" data-name="{{ $order->order_number }}"
                            data-total-amount="{{ $order->grand_total }}" data-paid-amount="{{ $order->paid_amount }}"
                            data-url="{{ url('/erp/sales/sale-list/mark-as-paid/' . $order->id) }}">
                            <i class="feather feather-check"></i>
                            <span>Mark as Paid</span>
                        </button>
                    </li>
                @endif
                <li>
                    <a href="/erp/sales/sale-list/payment-history/{{ $order->id }}" class="dropdown-item">
                        <i class="feather feather-dollar-sign me-3"></i>
                        <span>Payment History</span>
                    </a>
                </li>
                @if ($order->payment_status === 'Overpaid')
                    <li>
                        <button type="button" class="dropdown-item btn-return-money" data-bs-toggle="modal"
                            data-bs-target="#modalReturnMoney" data-id="{{ $order->id }}"
                            data-name="{{ $order->order_number }}"
                            data-overpaid-amount="{{ $order->paid_amount - $order->grand_total }}"
                            data-url="{{ url('/erp/sales/sale-list/return-money/' . $order->id) }}">
                            <i class="feather feather-corner-down-left me-3"></i>
                            <span>Return Money</span>
                        </button>
                    </li>
                @endif
            </div>
            <div class="action-col">
                <span class="action-title">General</span>
                <li>
                    <a class="dropdown-item" href="/erp/sales/sale-list/edit-order/{{ $order->id }}">
                        <i class="feather feather-edit-3 me-3"></i>
                        <span>Edit</span>
                    </a>
                </li>
                <li>
                    <a href="/erp/sales/sale-list/edit-history/{{ $order->id }}" class="dropdown-item">
                        <i class="feather feather-clock me-3"></i>
                        <span>Edit History</span>
                    </a>
                </li>
                <li>
                    <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                        data-bs-target="#modalDeleteOrder" data-id="{{ $order->id }}"
                        data-name="{{ $order->name }}"
                        data-url="{{ url('/erp/sales/sale-list/delete/' . $order->id) }}">
                        <i class="feather feather-trash-2 me-3"></i>
                        <span>Delete</span>
                    </button>
                </li>
                @php $isOwner = auth()->check() && auth()->user()->role === 'Owner'; @endphp

                @if ($isOwner)
                    {{-- <li>
                        <button type="button" class="dropdown-item text-danger btn-force-delete-owner"
                            data-bs-toggle="modal" data-bs-target="#modalForceDeleteOwner"
                            data-id="{{ $order->id }}" data-name="{{ $order->order_number }}"
                            data-url="{{ route('sales.forceDeleteOwner', $order->id) }}">
                            <i class="feather feather-zap-off me-3"></i>
                            <span>Force Delete (Owner)</span>
                        </button>
                    </li> --}}

                    <li>
                        <button type="button" class="dropdown-item text-danger btn-force-delete-owner"
                            data-bs-toggle="modal" data-bs-target="#modalForceDeleteOwner"
                            data-id="{{ $order->id }}" data-name="{{ $order->order_number }}"
                            data-url="{{ route('sales.sale-list.forceDeleteOwner', $order->id) }}">
                            <i class="feather feather-zap-off me-3"></i>
                            <span>Force Delete (Owner)</span>
                        </button>
                    </li>
                @endif
            </div>
        </div>
        {{-- <li>
            <hr class="my-2">
        </li> --}}
        {{-- <li>
            <hr class="my-2">
        </li> --}}
    </ul>
</div>
