<div class="table-responsive">
    <table class="table table-sm table-bordered mb-0 machine-assign-table">
        <thead>
            <tr>
                <th style="width: 22%;">Customer</th>
                <th style="width: 13%;">Preview</th>
                <th style="width: 30%;">Product</th>
                <th style="width: 15%;">Qty</th>
                <th style="width: 20%;">Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groups as $group)
                @php
                    // 🔧 Satu blok = satu customer (bisa gabungan beberapa invoice)
                    $order = $group['order'];
                    $groupAssigns = $group['assigns'];
                    $customerName = $order?->customer?->name;
                    $contact = \App\Support\PhoneNumber::toLocalIndonesian($order?->order_whatsapp_number);
                    $orderNotes = $order?->notes;
                @endphp

                @foreach ($groupAssigns as $index => $assign)
                    @php
                        $images = [];

                        if ($assign->progressItem?->designItem?->preview_image) {
                            $images = json_decode($assign->progressItem->designItem->preview_image, true) ?? [];
                        }

                        $conversion = max((float) ($assign->progressItem?->unit_conversion_value ?? 1), 1);
                    @endphp

                    <tr>
                        @if ($index === 0)
                            {{-- 🔹 Cukup nama customer + kontak, tanpa nomor invoice --}}
                            <td rowspan="{{ $groupAssigns->count() }}" class="align-top">
                                <div class="fw-bold text-dark">{{ $customerName ?? '-' }}</div>
                                <div class="text-muted small">{{ $contact ?: '-' }}</div>
                            </td>
                        @endif

                        <td>
                            @if (!empty($images))
                                <button type="button" class="btn btn-sm btn-outline-info preview-btn"
                                    data-images='@json($images)'
                                    data-product="{{ $assign->progressItem?->product?->name ?? '-' }}"
                                    data-order_note="{{ $orderNotes ?? '-' }}">
                                    <i class="feather-eye me-1"></i> Preview
                                </button>
                            @else
                                <span class="text-muted small fst-italic">No preview</span>
                            @endif
                        </td>

                        <td>
                            <span class="fw-bold text-dark">
                                {{ $assign->progressItem?->product?->name ?? '-' }}
                            </span>
                        </td>

                        <td>
                            <span class="fw-bold text-success">
                                {{ number_format($assign->assigned_quantity / $conversion, 0, ',', '.') }}
                            </span>
                            {{ $assign->progressItem?->unit_name }}
                        </td>

                        <td>
                            <span class="fw-bold text-dark">{{ $assign->note ?? '-' }}</span>
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
