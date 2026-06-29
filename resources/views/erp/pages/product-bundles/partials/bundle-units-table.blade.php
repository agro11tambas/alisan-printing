<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle">
        <thead>
            <tr>
                <th>Unit</th>
                {{-- <th>Rasio</th> --}}
                <th>Fixed Cost</th>
                <th>Margin</th>
                <th>Sale Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bundle->unitConversions as $unit)
                @php
                    $fixedCost = $bundle->items->sum(function ($item) use ($unit) {
                        $productUnit = $item->product?->unitConversions?->firstWhere('unit_id', $unit->unit_id);

                        return (float) ($productUnit?->fixed_cost ?? 0) * (float) ($item->quantity ?? 1);
                    });

                    $margin = $bundle->items->sum(function ($item) use ($unit) {
                        $productUnit = $item->product?->unitConversions?->firstWhere('unit_id', $unit->unit_id);

                        return (float) ($productUnit?->margin ?? 0) * (float) ($item->quantity ?? 1);
                    });

                    $salePrice = $fixedCost + $margin;
                @endphp

                <tr>
                    <td class="fw-semibold text-dark">
                        {{ $unit->unit->name ?? '-' }}
                    </td>

                    <td>
                        Rp {{ number_format($fixedCost, 0, ',', '.') }}
                    </td>

                    <td>
                        Rp {{ number_format($margin, 0, ',', '.') }}
                    </td>

                    <td class="fw-semibold text-success">
                        Rp {{ number_format($salePrice, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        Belum ada unit
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
