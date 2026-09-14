<div class="table-responsive">
    {{-- 🔹 lebar kolom dikunci lewat colgroup + table-layout: fixed supaya tiap mesin rata --}}
    <table class="table table-sm table-bordered mb-0 machine-assign-table">
        <colgroup>
            <col style="width: 22%;">
            <col style="width: 10%;">
            <col style="width: 24%;">
            <col style="width: 12%;">
            <col style="width: 16%;">
            <col style="width: 16%;">
        </colgroup>
        <thead>
            <tr>
                <th>Customer</th>
                <th>Preview</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Note</th>
                <th>Design Note</th>
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
                    // 🔹 sale note semua invoice di blok ini (dipisah koma), untuk modal preview & kolom customer
                    $orderNotes = $group['sale_notes'] ?? null;
                @endphp

                @foreach ($lines as $index => $line)
                    <tr>
                        @if ($index === 0)
                            {{-- 🔹 Nama customer + kontak + branch + sale note, tanpa nomor invoice --}}
                            <td rowspan="{{ $lines->count() }}" class="align-top">
                                <div class="fw-bold text-dark">{{ $customerName ?? '-' }}</div>
                                <div class="text-muted small">{{ $contact ?: '-' }}</div>
                                @if ($businessName)
                                    <div class="text-muted small">{{ $businessName }}</div>
                                @endif
                                @if ($orderNotes)
                                    <div class="small text-primary mt-1 sale-note"><span class="fw-semibold">Sale Note:</span> {{ $orderNotes }}</div>
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

                        <td>
                            {{-- 🔹 catatan dari gambar design (preview_image) --}}
                            <span class="fw-bold text-dark">{{ $line['design_note'] ?? '-' }}</span>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Belum ada assign untuk mesin ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
