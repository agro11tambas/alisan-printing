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
                    $totalAssigned = $item->assigns()->sum('assigned_quantity');
                    $totalCompleted = $item->assigns()->sum('completed_quantity');
                    $totalDefect = $item->assigns()->sum('defect_quantity');
                    $totalReject = $item->assigns()->sum('reject_quantity');
                    $activeAssign = max($totalAssigned - ($totalCompleted + $totalDefect + $totalReject), 0);

                    $images = json_decode(optional($item->designItem)->preview_image ?? '[]', true);
                @endphp

                <tr>
                    {{-- Product --}}
                    <td>
                        <span class="fw-bold text-dark">{{ $item->product->name ?? '-' }}</span>
                    </td>

                    {{-- Preview --}}
                    <td>
                        @if (!empty($images))
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                @foreach ($images as $img)
                                    <div class="text-center">
                                        <a href="#" class="img-viewer" data-src="{{ asset($img['file']) }}"
                                            data-note="{{ $img['note'] ?? '' }}">
                                            <img src="{{ asset($img['file']) }}" width="90" height="70"
                                                style="border-radius:8px;object-fit:cover;border:1px solid #ddd;">
                                        </a>
                                        <p class="small text-muted mt-1">{{ $img['note'] ?? '-' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-muted small fst-italic">No preview</span>
                        @endif
                    </td>

                    {{-- Progress --}}
                    <td>
                        <span class="fw-bold text-success">{{ number_format($totalCompleted, 0, ',', '.') }}</span> /
                        <span class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</span>
                    </td>

                    {{-- Assign --}}
                    <td>
                        <span class="fw-bold text-danger">{{ number_format($activeAssign, 0, ',', '.') }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>