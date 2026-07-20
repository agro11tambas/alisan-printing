<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Freight</th>
                <th>Total</th>
                <th>Stock In</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->purchaseItems as $item)
            <tr>
                <td>
                    <span class="fw-bold text-primary">{{ $item->purchaseProduct->name }}</span>
                </td>
                <td><span class="fw-bold text-success">{{ $item->quantity }}</span></td>
                <td>Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->freight, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                @php($stockInInUnit = ($item->stock_in ?? 0) / max(1, $item->unit_conversion_value ?? 1))
                <td><span class="fw-bold text-primary">{{ number_format($stockInInUnit, 0, ',', '.') }}/{{ number_format($item->quantity, 0, ',', '.') }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
