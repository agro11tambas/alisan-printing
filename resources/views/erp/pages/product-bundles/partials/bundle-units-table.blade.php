<div class="table-responsive">
    <table class="table bg-transparent table-small table-bordered mb-0 align-middle">
        <thead>
            <tr>
                <th>Unit</th>
                <th>Rasio</th>
                <th>Fixed Cost</th>
                <th>Margin</th>
                <th>Sale Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($bundle->unitConversions as $unit)
                <tr>
                    <td class="fw-semibold text-dark">
                        {{ $unit->unit->name ?? '-' }}
                    </td>
                    <td>
                        {{ rtrim(rtrim(number_format((float) $unit->ratio_value, 0, ',', '.'), '0'), ',') }}
                    </td>
                    <td>
                        Rp {{ number_format((float) $unit->fixed_cost, 0, ',', '.') }}
                    </td>
                    <td>
                        Rp {{ number_format((float) $unit->margin, 0, ',', '.') }}
                    </td>
                    <td class="fw-semibold text-success">
                        Rp {{ number_format((float) $unit->sale_price, 0, ',', '.') }}
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
