@php
    $remaining = (float) $bill->remaining_amount;
    $paid = (float) $bill->paid_amount;
    $label = $bill->waybill_number ?: 'Tanpa No. Surat Jalan';
    $historyUrl = url('/erp/purchases/freight-payments/payment-history') . '?waybill_key=' . urlencode($bill->waybill_key);
@endphp

<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <div class="action-grid">
            <div class="action-col">
                <div class="action-title">Pembayaran</div>
                @if ($remaining > 0.5)
                    <li>
                        <button type="button" class="dropdown-item btn-mark-paid-freight"
                            data-waybill-key="{{ $bill->waybill_key }}" data-waybill-number="{{ $label }}"
                            data-supplier="{{ $bill->supplier_name }}" data-total="{{ (float) $bill->total_freight }}"
                            data-paid="{{ $paid }}" data-remaining="{{ $remaining }}">
                            <i class="feather feather-truck me-3"></i>
                            <span>Mark as Paid (Freight)</span>
                        </button>
                    </li>
                @else
                    <li>
                        <span class="dropdown-item text-muted">
                            <i class="feather feather-check-circle me-3"></i>
                            <span>Freight sudah lunas</span>
                        </span>
                    </li>
                @endif
            </div>

            <div class="action-col">
                <div class="action-title">Riwayat</div>
                <li>
                    <a href="{{ $historyUrl }}" class="dropdown-item">
                        <i class="feather feather-dollar-sign me-3"></i>
                        <span>Payment History</span>
                    </a>
                </li>
            </div>

            <div class="action-col">
                <div class="action-title">Rincian</div>
                <li>
                    <a href="{{ $historyUrl }}" class="dropdown-item {{ $paid > 0 ? 'text-danger' : 'text-muted' }}">
                        <i class="feather feather-trash-2 me-3"></i>
                        <span>{{ $paid > 0 ? 'Delete Payment' : 'Belum ada pembayaran' }}</span>
                    </a>
                </li>
            </div>
        </div>
    </ul>
</div>
