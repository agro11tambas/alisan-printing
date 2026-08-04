<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle">
        <thead>
            <tr>
                <th>Mode</th>
                <th>Unit</th>
                <th>Rasio</th>
                <th>Fixed Cost</th>
                <th>Margin</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($product->unitConversions as $unit)
                @forelse ($unit->prices->sortBy(fn ($price) => $price->priceMode?->sort_order ?? PHP_INT_MAX) as $price)
                    <tr>
                        <td class="fw-semibold text-primary">
                            {{ $price->priceMode->name ?? '-' }}
                        </td>
                        <td class="fw-semibold text-dark">
                            {{ $unit->unit->name ?? '-' }}
                        </td>
                        <td>{{ number_format((float) $unit->ratio_value, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $price->fixed_cost, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $price->margin, 0, ',', '.') }}</td>
                        <td class="fw-semibold text-success">
                            Rp {{ number_format((float) $price->sale_price, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-muted">-</td>
                        <td class="fw-semibold text-dark">{{ $unit->unit->name ?? '-' }}</td>
                        <td>{{ number_format((float) $unit->ratio_value, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $unit->fixed_cost, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format((float) $unit->margin, 0, ',', '.') }}</td>
                        <td class="fw-semibold text-success">
                            Rp {{ number_format((float) $unit->sale_price, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforelse
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">Belum ada unit dan harga</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>