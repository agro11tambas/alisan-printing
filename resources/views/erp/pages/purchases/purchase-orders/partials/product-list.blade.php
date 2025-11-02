<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th style="width: 50%;">Product</th>
                <th style="width: 50%;">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->purchaseItems as $item)
            <tr>
                <td>
                    <span class="fw-bold text-primary">{{ $item->purchaseProduct->name }}</span>
                </td>
                <td><span class="fw-bold text-success">{{ $item->quantity }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
