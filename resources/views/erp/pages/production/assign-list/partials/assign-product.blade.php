<div class="table-responsive">
    <table class="table table-small table-bordered">
        <thead>
            <tr>
                <th style="width: 13%;">Preview</th>
                <th style="width: 22%;">Product</th>
                <th style="width: 13%;">Mesin</th>
                <th style="width: 15%;">Operator</th>
                <th style="width: 17%;">Assigned</th>
                {{-- <th style="width: 10%;">Defect Product</th>
                <th style="width: 10%;">Reject Product</th> --}}
                <th style="width: 10%;">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($assigns as $assign)
                @php
                    // $images = json_decode(optional($assign->progressItem->designItem)->preview_image ?? '[]', true);
                    $images = [];

                    if ($assign->progressItem?->designItem?->preview_image) {
                        $images = json_decode($assign->progressItem->designItem->preview_image, true) ?? [];
                    }

                    // 🔧 Operator dipilih saat input progress, jadi diambil dari history.
                    //    Data lama masih menyimpan operator di assign.
                    $operatorNames = $assign->histories
                        ->map(fn($history) => $history->operators?->name)
                        ->filter()
                        ->unique()
                        ->values();

                    if ($operatorNames->isEmpty() && $assign->operator) {
                        $operatorNames = collect([$assign->operator->name]);
                    }
                @endphp
                <tr>
                    {{-- PREVIEW BUTTON --}}
                    <td>
                        @if (!empty($images))
                            <button class="btn btn-sm btn-outline-info preview-btn"
                                data-images='@json($images)'
                                data-product="{{ $assign->progressItem?->product?->name ?? '-' }}"
                                data-order_note="{{ $assign->progressItem?->progress?->order?->notes ?? '-' }}">
                                <i class="feather-eye me-1"></i> Preview
                            </button>
                        @else
                            <span class="text-muted small fst-italic">No preview</span>
                        @endif
                    </td>
                    <td>
                        <span class="fw-bold text-dark">{{ $assign->progressItem?->product?->name ?? '-' }}
                        </span>
                    </td>
                    <td>
                        {{-- 🔧 mesin per produk --}}
                        <span class="fw-bold text-dark">{{ $assign->machine?->name ?? '-' }}</span>
                    </td>
                    <td>
                        <span class="fw-bold text-dark">
                            {{ $operatorNames->isNotEmpty() ? $operatorNames->implode(', ') : '-' }}
                        </span>
                    </td>
                    <td><span
                            class="fw-bold text-success">{{ number_format($assign->assigned_quantity / max((float) ($assign->progressItem?->unit_conversion_value ?? 1), 1), 0, ',', '.') }}</span>
                        {{ $assign->progressItem?->unit_name }}
                        {{-- /<span class="fw-bold text-primary">{{ number_format($assign->assigned_quantity, 0, ',', '.') }}</span> --}}
                    </td>
                    {{-- <td><span class="fw-bold text-danger">{{ number_format($assign->defect_quantity, 0, ',', '.') }}</span></td>
                <td><span class="fw-bold text-warning">{{ number_format($assign->reject_quantity, 0, ',', '.') }}</span></td> --}}
                    <td><span class="fw-bold text-dark">{{ $assign->note ?? '-' }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
