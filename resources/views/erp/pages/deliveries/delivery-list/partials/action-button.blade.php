<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">

        {{-- ===================== ADMIN ===================== --}}
        @if(Auth::user()->role === 'Admin' || Auth::user()->role === 'Owner')
        <li>
            <a href="/erp/deliveries/delivery-list/print-waybill/{{ $dl->id }}" target="_blank" class="dropdown-item">
                <i class="feather feather-printer"></i>
                <span>Print Waybill</span>
            </a>
        </li>

        @if($dl->proof_delivery && $dl->proof_waybill && $dl->status !== 'Finished')
        <li>
            <button type="button"
                class="dropdown-item btn-verify"
                data-bs-toggle="modal"
                data-bs-target="#modalChangeStatus"
                data-id="{{ $dl->id }}"
                data-name="{{ $dl->shipment_number ?? 'Delivery #' . $dl->id }}"
                data-url="{{ route('delivery-list.verify', $dl->id) }}">
                <i class="feather feather-check me-3"></i>
                <span>Verified</span>
            </button>
        </li>
        @endif
        
        <li>
            <a href="{{ url('/erp/deliveries/delivery-list/edit-delivery-list/' . $dl->id) }}" class="dropdown-item">
                <i class="feather feather-edit"></i>
                <span>Edit Delivery List</span>
            </a>
        </li>
        @endif

        {{-- ===================== KURIR ===================== --}}
        @if(Auth::user()->role === 'Kurir' || Auth::user()->role === 'Owner')
        @if(!$dl->proof_delivery)
        <li>
            <a href="javascript:void(0);"
                class="dropdown-item btn-upload-delivery"
                data-id="{{ $dl->id }}"
                data-url="{{ route('delivery-list.upload-proof', ['id' => $dl->id, 'type' => 'delivery']) }}">
                <i class="feather feather-upload"></i> Bukti Pengantaran
            </a>
        </li>
        @endif

        @if(!$dl->proof_waybill)
        <li>
            <a href="javascript:void(0);"
                class="dropdown-item btn-upload-waybill"
                data-id="{{ $dl->id }}"
                data-url="{{ route('delivery-list.upload-proof', ['id' => $dl->id, 'type' => 'waybill']) }}">
                <i class="feather feather-file-text"></i> Bukti Surat Jalan
            </a>
        </li>
        @endif
        @endif
    </ul>
</div>