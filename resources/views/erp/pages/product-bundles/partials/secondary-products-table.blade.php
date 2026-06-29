{{-- <div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle">
        <thead>
            <tr>
                <th>Secondary Product</th>
                <th>Bundle Units</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($secondaryBundles as $item)
                <tr>
                    <td class="fw-semibold text-dark">
                        {{ $item['secondary_name'] }}
                        <div>
                            <small class="text-muted">{{ $item['sku'] }}</small>
                        </div>
                    </td>

                    <td>
                        {!! $item['bundle_units'] !!}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        Belum ada secondary product
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div> --}}

<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle">
        <thead>
            <tr>
                <th>Secondary Product</th>
                <th>Unit</th>
                <th>Fixed Cost</th>
                <th>Margin</th>
                <th>Sale Price</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($secondaryBundles as $item)
                @foreach ($item['bundle']->unitConversions as $unitIndex => $unit)
                    @php
                        $fixedCost = $item['bundle']->items->sum(function ($bundleItem) use ($unit) {
                            $productUnit = $bundleItem->product?->unitConversions?->firstWhere(
                                'unit_id',
                                $unit->unit_id,
                            );

                            return (float) ($productUnit?->fixed_cost ?? 0) * (float) ($bundleItem->quantity ?? 1);
                        });

                        $margin = $item['bundle']->items->sum(function ($bundleItem) use ($unit) {
                            $productUnit = $bundleItem->product?->unitConversions?->firstWhere(
                                'unit_id',
                                $unit->unit_id,
                            );

                            return (float) ($productUnit?->margin ?? 0) * (float) ($bundleItem->quantity ?? 1);
                        });

                        $salePrice = $fixedCost + $margin;
                    @endphp

                    <tr>
                        @if ($unitIndex === 0)
                            <td rowspan="{{ $item['bundle']->unitConversions->count() }}" class="fw-semibold text-dark">
                                {{ $item['secondary_name'] }}
                                <div>
                                    <small class="text-muted">{{ $item['sku'] }}</small>
                                </div>
                            </td>
                        @endif

                        <td class="fw-semibold text-dark">{{ $unit->unit->name ?? '-' }}</td>
                        <td>Rp {{ number_format($fixedCost, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($margin, 0, ',', '.') }}</td>
                        <td class="fw-semibold text-success">
                            Rp {{ number_format($salePrice, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        Belum ada secondary product
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
