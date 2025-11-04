<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @if (Auth::user()->role === 'Admin' || Auth::user()->role === 'Owner')

            {{-- ✅ tampilkan tombol hanya jika belum Finished --}}
            @if ($dl->status !== 'Finished')
                <li>
                    <a href="/erp/deliveries/delivery-list/print-waybill/{{ $dl->id }}" target="_blank"
                        class="dropdown-item">
                        <i class="feather feather-printer"></i>
                        <span>Print Waybill</span>
                    </a>
                </li>

                <li>
                    <button type="button" class="dropdown-item btn-verify" data-bs-toggle="modal"
                        data-bs-target="#modalChangeStatus" data-id="{{ $dl->id }}"
                        data-name="{{ $dl->shipment_number ?? 'Delivery #' . $dl->id }}"
                        data-url="{{ route('delivery-list.verify', $dl->id) }}">
                        <i class="feather feather-check me-3"></i>
                        <span>Verified</span>
                    </button>
                </li>
                {{-- @if ($dl->proof_photos)
                @endif --}}

                <li>
                    <a href="{{ url('/erp/deliveries/delivery-list/edit-delivery-list/' . $dl->id) }}"
                        class="dropdown-item">
                        <i class="feather feather-edit"></i>
                        <span>Edit Delivery List</span>
                    </a>
                </li>

                @if (!$dl->proof_photos)
                <li>
                    <button type="button" class="dropdown-item btn-delete-delivery" data-id="{{ $dl->id }}"
                        data-name="{{ $dl->shipment_number ?? 'Delivery #' . $dl->id }}"
                        data-url="{{ route('delivery-list.destroy', $dl->id) }}">
                        <i class="feather feather-trash-2 me-3"></i>
                        <span>Delete Delivery List</span>
                    </button>
                </li>
                @endif
            @endif
        @endif

        @if (Auth::user()->role === 'Kurir' || Auth::user()->role === 'Admin' || Auth::user()->role === 'Owner')
            {{-- ✅ Tombol upload bukti hanya muncul jika belum upload bukti --}}
            @if (empty($dl->proof_photos) || $dl->proof_photos === '[]')
                <li>
                    <a href="javascript:void(0);" class="dropdown-item btn-upload-proof" data-id="{{ $dl->id }}"
                        data-url="{{ route('delivery-list.upload-proof', $dl->id) }}"
                        data-photos='{{ $dl->proof_photos ?? '[]' }}'>
                        <i class="feather feather-upload"></i> Upload Bukti
                    </a>

                </li>
            @endif
        @endif

    </ul>
</div>
