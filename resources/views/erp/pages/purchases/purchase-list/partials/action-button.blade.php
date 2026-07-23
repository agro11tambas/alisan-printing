<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <div class="action-grid">
            <div class="action-col">
                @if ($purchase->payment_status !== 'Paid')
                    @if ($purchase->remaining_amount_product > 0)
                        <li>
                            <button type="button" class="dropdown-item btn-mark-paid-product" data-bs-toggle="modal"
                                data-bs-target="#modalChangeStatusProduct" data-id="{{ $purchase->id }}"
                                data-total-amount-product="{{ $purchase->total_amount_product }}"
                                data-paid-amount-product="{{ $purchase->paid_amount_product }}"
                                data-url="{{ url('/erp/purchases/purchase-list/mark-as-paid-product/' . $purchase->id) }}">
                                <i class="feather feather-box me-3"></i>
                                <span>Mark as Paid (Product)</span>
                            </button>
                        </li>
                    @endif

                    @if ($purchase->remaining_amount_freight > 0)
                        <li>
                            <button type="button" class="dropdown-item btn-mark-paid-freight" data-bs-toggle="modal"
                                data-bs-target="#modalChangeStatusFreight" data-id="{{ $purchase->id }}"
                                data-total-amount-freight="{{ $purchase->total_amount_freight }}"
                                data-paid-amount-freight="{{ $purchase->paid_amount_freight }}"
                                data-url="{{ url('/erp/purchases/purchase-list/mark-as-paid-freight/' . $purchase->id) }}">
                                <i class="feather feather-truck me-3"></i>
                                <span>Mark as Paid (Freight)</span>
                            </button>
                        </li>
                    @endif
                @endif
                <li>
                    <a href="/erp/purchases/purchase-list/payment-history/{{ $purchase->id }}" class="dropdown-item">
                        <i class="feather feather-dollar-sign me-3"></i>
                        <span>Payment History</span>
                    </a>
                </li>
                @if (!$purchase->is_fully_returned && $purchase->hasStockIn())
                    {{-- <li>
                        <hr class="my-1">
                    </li> --}}
                    <li>
                        <a href="/erp/purchases/purchase-returns/create-purchase-return/{{ $purchase->id }}"
                            class="dropdown-item">
                            <i class="feather feather-corner-down-left me-3"></i>
                            <span>Make Purchase Return</span>
                        </a>
                    </li>
                @endif
            </div>
            {{-- <li>
            <hr class="my-1">
        </li> --}}
            <div class="action-col">
                <li>
                    <a href="/erp/purchases/purchase-list/detail-purchase/{{ $purchase->id }}" class="dropdown-item">
                        <i class="feather feather-eye"></i>
                        <span>Purchase Detail</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="/erp/purchases/purchase-list/edit-purchase/{{ $purchase->id }}">
                        <i class="feather feather-edit-3 me-3"></i>
                        <span>Edit</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="/erp/purchases/purchase-list/edit-history/{{ $purchase->id }}">
                        <i class="feather feather-clock me-3"></i>
                        <span>Edit History</span>
                    </a>
                </li>
                @if ($purchase->hasInventoryStockIn())
                    @php
                        $inventory = $purchase->firstInventoryForStockIn();
                    @endphp

                    @if ($inventory)
                        <li>
                            <a class="dropdown-item"
                                href="{{ url('/erp/inventory/stock-in/history/' . $inventory->id) }}">
                                <i class="feather feather-info me-3"></i>
                                <span>History Stock In</span>
                            </a>
                        </li>
                    @endif
                @endif

            </div>
            <div class="action-col">
                <li>
                    <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                        data-bs-target="#modalDeletePurchase" data-id="{{ $purchase->id }}"
                        data-name="{{ $purchase->name }}" data-total-amount="{{ $purchase->total_amount }}"
                        data-paid-amount="{{ $purchase->paid_amount }}"
                        data-url="{{ url('/erp/purchases/purchase-list/delete/' . $purchase->id) }}">
                        <i class="feather feather-trash-2 me-3"></i>
                        <span>Delete</span>
                    </button>
                </li>

                @php $isOwner = auth()->check() && auth()->user()->role === 'Owner'; @endphp

                @if ($isOwner && $purchase->parent_purchase_id)
                    <li>
                        <button type="button" class="dropdown-item text-danger btn-force-delete-owner"
                            data-bs-toggle="modal" data-bs-target="#modalForceDeleteOwnerPurchase"
                            data-id="{{ $purchase->id }}"
                            data-name="{{ $purchase->purchase_number ?? 'Purchase #' . $purchase->id }}"
                            data-url="{{ route('purchases.purchase-list.forceDeleteOwner', $purchase->id) }}">
                            <i class="feather feather-zap-off me-3"></i>
                            <span>Force Delete (Owner)</span>
                        </button>
                    </li>
                @endif
            </div>
        </div>
    </ul>
</div>
