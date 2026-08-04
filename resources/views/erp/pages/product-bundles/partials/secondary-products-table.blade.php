<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle">
        <thead>
            <tr>
                <th>Secondary Product</th>
                <th>Mode</th>
                <th>Unit</th>
                <th>Rasio</th>
                <th>Fixed Cost</th>
                <th>Margin</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($secondaryBundles as $item)
                @php
                    $rowCount = $item['bundle']->unitConversions->sum(
                        fn ($unit) => max(1, $unit->prices->count())
                    );
                    $secondaryShown = false;
                @endphp

                @foreach ($item['bundle']->unitConversions as $unit)
                    @forelse ($unit->prices->sortBy(fn ($price) => $price->priceMode?->sort_order ?? PHP_INT_MAX) as $price)
                        <tr>
                            @unless ($secondaryShown)
                                <td rowspan="{{ $rowCount }}" class="fw-semibold text-dark">
                                    {{ $item['secondary_name'] }}
                                    <div><small class="text-muted">{{ $item['sku'] }}</small></div>
                                </td>
                                @php($secondaryShown = true)
                            @endunless
                            <td class="fw-semibold text-primary">{{ $price->priceMode->name ?? '-' }}</td>
                            <td class="fw-semibold text-dark">{{ $unit->unit->name ?? '-' }}</td>
                            <td>{{ number_format((float) $unit->ratio_value, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $price->fixed_cost, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $price->margin, 0, ',', '.') }}</td>
                            <td class="fw-semibold text-success">
                                Rp {{ number_format((float) $price->sale_price, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            @unless ($secondaryShown)
                                <td rowspan="{{ $rowCount }}" class="fw-semibold text-dark">
                                    {{ $item['secondary_name'] }}
                                    <div><small class="text-muted">{{ $item['sku'] }}</small></div>
                                </td>
                                @php($secondaryShown = true)
                            @endunless
                            <td class="text-muted">-</td>
                            <td class="fw-semibold text-dark">{{ $unit->unit->name ?? '-' }}</td>
                            <td>{{ number_format((float) $unit->ratio_value, 0, ',', '.') }}</td>
                            <td colspan="3" class="text-muted text-center">Harga belum tersedia</td>
                        </tr>
                    @endforelse
                @endforeach
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Belum ada secondary product</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>