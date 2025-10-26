<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 33%;">Operator</th>
                <th style="width: 33%;">Progress</th>
                <th style="width: 33%;">Defect Product</th>
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
                <td><span class="fw-bold text-success">{{ $assign->change_quantity }}</span>/<span class="fw-bold text-primary">{{ number_format($assign->assigned_quantity) }}</span></td>
                <td><span class="fw-bold text-danger">{{ $assign->defect_quantity }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>