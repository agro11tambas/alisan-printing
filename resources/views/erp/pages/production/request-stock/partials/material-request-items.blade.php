<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 25%;">Product</th>
                <!-- <th style="width: 25%;">Progress</th>
                <th style="width: 25%;">Incoming Stock</th> -->
                <th style="width: 25%;">Verified</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($materialRequest->items as $item)
            <tr>
                <td>
                    <span class="fw-bold text-dark">
                        {{ $item->product->name }}
                    </span>
                </td>
                <!-- <td><span class="fw-bold text-success">{{ $item->received_qty }}</span>/<span class="fw-bold text-primary">{{ $item->requested_qty }}</td>
                <td><span class="fw-bold text-danger">{{ $item->requested_qty - $item->issued_qty }}</span></td> -->
                <td>
                    <span class="fw-bold text-success">
                        {{ number_format($item->issued_qty, 0, ',', '.') }}
                    </span>
                    /
                    <span class="fw-bold text-primary">
                        {{ number_format($item->requested_qty, 0, ',', '.') }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>