<div class="table-responsive">
    <table class="table table-sm table-bordered mb-0 machine-assign-table">
        <thead>
            <tr>
                <th style="width: 25%;">Customer</th>
                <th style="width: 12%;">Preview</th>
                <th style="width: 28%;">Product</th>
                <th style="width: 15%;">Qty</th>
                <th style="width: 20%;">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $group)
                @php
                    // 🔧 Satu blok = satu customer (bisa gabungan beberapa invoice)
                    $order = $group['order'];
                    $lines = $group['lines'];
                    $customerName = $order?->customer?->name;
                    $contact = \App\Support\PhoneNumber::toLocalIndonesian($order?->order_whatsapp_number);
                    // 🔧 cukup nama branch/outlet, alamat lengkap tidak ditampilkan
                    $businessName = $order?->customerAddress?->business_name;
                    $orderNotes = $order?->notes;
                @endphp

                @foreach ($lines as $index => $line)
                    <tr>
                        @if ($index === 0)
                            {{-- 🔹 Nama customer + kontak + alamat, tanpa nomor invoice --}}
                            <td rowspan="{{ $lines->count() }}" class="align-top">
                                <div class="fw-bold text-dark">{{ $customerName ?? '-' }}</div>
                                <div class="text-muted small">{{ $contact ?: '-' }}</div>
                                @if ($businessName)
                                    <div class="text-muted small">{{ $businessName }}</div>
                                @endif
                            </td>
                        @endif

                        <td>
                            @if (!empty($line['images']))
                                <button type="button" class="btn btn-sm btn-outline-info preview-btn"
                                    data-images='@json($line['images'])' data-product="{{ $line['product'] }}"
                                    data-order_note="{{ $orderNotes ?? '-' }}">
                                    <i class="feather-eye me-1"></i> Preview
                                </button>
                            @else
                                <span class="text-muted small fst-italic">No preview</span>
                            @endif
                        </td>

                        <td>
                            <span class="fw-bold text-dark">{{ $line['product'] }}</span>
                        </td>

                        <td>
                            <span class="fw-bold text-success">
                                {{ number_format($line['qty'], 0, ',', '.') }}
                            </span>
                            {{ $line['unit'] }}
                        </td>

                        <td>
                            <span class="fw-bold text-dark">{{ $line['note'] ?? '-' }}</span>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Belum ada assign untuk mesin ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
