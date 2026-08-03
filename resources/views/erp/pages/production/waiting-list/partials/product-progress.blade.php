<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered align-middle">
        <thead>
            <tr>
                <th style="width: 25%;">Product</th>
                <th style="width: 25%;">Preview</th>
                <th style="width: 25%;">Progress</th>
                <th style="width: 25%;">Assign</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($progress->items as $item)
                @php
                    $totalAssigned = $item->assigns->sum('assigned_quantity');
                    $totalCompleted = $item->assigns->sum('completed_quantity');
                    $totalDefect = $item->assigns->sum('defect_quantity');
                    $totalReject = $item->assigns->sum('reject_quantity');

                    $unitConversionValue = (float) ($item->unit_conversion_value ?? 1);

                    if ($unitConversionValue <= 0) {
                        $unitConversionValue = 1;
                    }

                    $activeAssign = max($totalAssigned - ($totalCompleted + $totalDefect + $totalReject), 0);
                    $activeAssignDisplay = $activeAssign / $unitConversionValue;

                    $totalAssignedDisplay = $totalAssigned / $unitConversionValue;
                    $totalCompletedDisplay = $totalCompleted / $unitConversionValue;
                    $totalDefectDisplay = $totalDefect / $unitConversionValue;
                    $totalRejectDisplay = $totalReject / $unitConversionValue;
                    $quantityDisplay = $item->quantity / $unitConversionValue;

                    $images = json_decode(optional($item->designItem)->preview_image ?? '[]', true);
                @endphp

                <tr>
                    <td>
                        <span class="fw-bold text-dark">{{ $item->product->name ?? '-' }}</span>
                    </td>
                    <td>
                        @if (!empty($images))
                            <button class="btn btn-sm btn-outline-info preview-btn"
                                data-images='@json($images)'
                                data-product="{{ $item->product->name ?? '-' }}"
                                data-order_note="{{ $progress->order->notes ?? '-' }}">
                                <i class="feather-eye me-1"></i> Preview
                            </button>
                        @else
                            <span class="text-muted small fst-italic">No preview</span>
                        @endif

                    </td>
                    <td>
                        <span
                            class="fw-bold text-success">{{ number_format($totalCompletedDisplay, 0, ',', '.') }}</span>
                        /
                        <span class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</span>
                        {{ $item->unit_name }}
                    </td>
                    <td>
                        <span class="fw-bold text-danger">{{ number_format($activeAssignDisplay, 0, ',', '.') }}</span>
                        {{ $item->unit_name }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
