<div class="table-responsive">
    <table class="table bg-transparent table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 33%;">Product</th>
                <th style="width: 33%;">Progress</th>
                <th style="width: 33%;">Assign</th>
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
                @endphp

                <tr>
                    <td>
                        <span class="fw-bold text-dark">{{ $item->product->name ?? '-' }}</span><br>
                    </td>
                    <td>
                        <span class="fw-bold text-success">{{ number_format($totalCompleted, 0, ',', '.') }}</span> /
                        <span class="fw-bold text-primary">{{ number_format($item->quantity, 0, ',', '.') }}</span><br>
                    </td>
                    <td>
                        <span class="fw-bold text-danger">{{ number_format($activeAssign, 0, ',', '.') }}</span><br>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
