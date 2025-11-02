<!DOCTYPE html>
<html>

<head>
    <title>Product Variations</title>
</head>

<body>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Nama Product</th>
                    <th>Harga</th>
                    <th>SKU</th>
                    <th>Avg Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($combinations as $combo)
                <tr style="border-bottom: 1px solid #ddd">
                    <td>
                        <a href="{{ asset($combo->image) }}" data-lightbox="combo-{{ $combo->id }}">
                            <img src="{{ asset($combo->image) }}"
                                width="50"
                                height="50"
                                style="border-radius: 50%; object-fit: cover; object-position: center;"
                                alt="Image">
                        </a>
                    </td>
                    <td>{{ $combo->name }}</td>
                    <td>Rp {{ number_format($combo->price, 0, ',', '.') }}</td>
                    <td>{{ $combo->sku }}</td>
                    <td>Rp {{ number_format($combo->avg_cost, 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="2">Belum ada kombinasi produk.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>

</html>
