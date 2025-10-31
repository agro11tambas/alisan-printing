<div class="table-responsive">
    <table class="table table-small table-bordered">
        <thead>
            <tr>
                <th style="width: 20%;">Operator</th>
                <th style="width: 20%;">Product</th>
                <th style="width: 20%;">Progress</th>
                <th style="width: 10%;">Defect Product</th>
                <th style="width: 10%;">Reject Product</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($assigns as $assign)
            <tr>
                <td>
                    <span class="fw-bold text-dark">@if($assign->operator)
                        {{ $assign->operator->name }}
                        @else
                        -
                        @endif
                    </span>
                </td>
                <td>
                    <span class="fw-bold text-dark">@if($assign->progressItem->product)
                        {{ $assign->progressItem->product->name }}
                        @else
                        -
                        @endif
                    </span>
                </td>
                <td><span class="fw-bold text-success">{{ number_format($assign->change_quantity, 0, ',', '.') }}</span>
                    {{-- /<span class="fw-bold text-primary">{{ number_format($assign->assigned_quantity, 0, ',', '.') }}</span> --}}
                </td>
                <td><span class="fw-bold text-danger">{{ number_format($assign->defect_quantity, 0, ',', '.') }}</span></td>
                <td><span class="fw-bold text-warning">{{ number_format($assign->reject_quantity, 0, ',', '.') }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>