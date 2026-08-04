@php
    $dynamicPrices = old('prices');
    if ($dynamicPrices === null && isset($product)) {
        $dynamicPrices = $product->unitConversions
            ->flatMap(fn ($conversion) => $conversion->prices->map(fn ($price) => [
                'price_mode_id' => $price->price_mode_id,
                'unit_id' => $conversion->unit_id,
                'fixed_cost' => $price->fixed_cost,
                'margin' => $price->margin,
                'sale_price' => $price->sale_price,
            ]))->values()->all();
    }
    $dynamicPrices = $dynamicPrices ?: [[
        'price_mode_id' => optional($priceModes->firstWhere('slug', 'polosan'))->id,
        'unit_id' => old('sale_unit_id', $product->sale_unit_id ?? null),
        'fixed_cost' => 0,
        'margin' => 0,
        'sale_price' => 0,
    ]];
@endphp
<style>
    #productUnitTable th:nth-child(4),
    #productUnitTable th:nth-child(5),
    #productUnitTable td:nth-child(4),
    #productUnitTable td:nth-child(5) {
        display: none;
    }
</style>

<div class="row mb-2 align-items-start">
    <div class="col-lg-2"><label class="fw-semibold">Dynamic Prices</label></div>
    <div class="col-lg-10 mb-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle" id="dynamicPriceTable">
                <thead><tr>
                    <th>Mode</th><th>Unit</th><th>Fixed Cost</th><th>Margin</th><th>Price</th>
                    <th style="width: 8%">Action</th>
                </tr></thead>
                <tbody id="dynamicPriceBody">
                    @foreach ($dynamicPrices as $index => $dynamicPrice)
                        <tr>
                            <td>
                                <select name="prices[{{ $index }}][price_mode_id]" class="form-control dynamic-mode" required>
                                    @foreach ($priceModes as $priceMode)
                                        <option value="{{ $priceMode->id }}"
                                            @selected((int) ($dynamicPrice['price_mode_id'] ?? 0) === $priceMode->id)
                                            @disabled(!$priceMode->is_active && (int) ($dynamicPrice['price_mode_id'] ?? 0) !== $priceMode->id)>
                                            {{ $priceMode->name }}{{ $priceMode->is_active ? '' : ' (Inactive)' }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="prices[{{ $index }}][unit_id]" class="form-control dynamic-unit"
                                    data-selected="{{ $dynamicPrice['unit_id'] ?? '' }}" required>
                                    <option value="">Choose Unit</option>
                                </select>
                            </td>
                            <td><input type="text" name="prices[{{ $index }}][fixed_cost]"
                                class="form-control dynamic-money dynamic-fixed-cost"
                                value="{{ number_format((float) ($dynamicPrice['fixed_cost'] ?? 0), 2, ',', '.') }}"></td>
                            <td><input type="text" name="prices[{{ $index }}][margin]"
                                class="form-control dynamic-money dynamic-margin"
                                value="{{ number_format((float) ($dynamicPrice['margin'] ?? 0), 2, ',', '.') }}"></td>
                            <td><input type="text" name="prices[{{ $index }}][sale_price]"
                                class="form-control dynamic-money dynamic-sale-price"
                                value="{{ number_format((float) ($dynamicPrice['sale_price'] ?? 0), 2, ',', '.') }}" readonly></td>
                            <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-price">
                                <i class="feather-trash-2"></i></button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-light-brand btn-sm" id="addDynamicPrice">
            <i class="feather-plus me-2"></i>Add Price
        </button>
        <small class="text-muted d-block mt-1">
            Harga dipilih berdasarkan kombinasi mode dan unit pada Sale List.
        </small>
    </div>
</div>

<script>
    (() => {
        const body = document.getElementById('dynamicPriceBody');
        const addButton = document.getElementById('addDynamicPrice');
        const form = document.getElementById('productForm');
        let priceIndex = {{ count($dynamicPrices) }};
        const availablePriceModes = @json($priceModes->where('is_active', true)->values()->map(
            fn ($mode) => ['id' => $mode->id, 'name' => $mode->name]
        ));
        const numberValue = value => {
            const normalized = String(value ?? '').replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
            return Number.parseFloat(normalized) || 0;
        };
        const formatMoney = value => Number(value || 0).toLocaleString('id-ID', {
            minimumFractionDigits: 0, maximumFractionDigits: 2
        });

        function configuredUnits() {
            const units = [];
            document.querySelectorAll('#productUnitBody .unit-select').forEach(select => {
                if (!select.value || units.some(unit => unit.id === select.value)) return;
                units.push({ id: select.value, name: select.options[select.selectedIndex]?.text || select.value });
            });
            return units;
        }

        function refreshUnitOptions() {
            const units = configuredUnits();
            body.querySelectorAll('.dynamic-unit').forEach(select => {
                const selected = select.value || select.dataset.selected || '';
                select.innerHTML = '<option value="">Choose Unit</option>';
                units.forEach(unit => select.add(new Option(unit.name, unit.id, false, unit.id === selected)));
                select.dataset.selected = select.value || selected;
            });
        }

        function calculate(row) {
            const fixedCost = numberValue(row.querySelector('.dynamic-fixed-cost').value);
            const margin = numberValue(row.querySelector('.dynamic-margin').value);
            row.querySelector('.dynamic-sale-price').value = formatMoney(fixedCost + margin);
        }

        function copyUnitFixedCost(row) {
            const unitId = row.querySelector('.dynamic-unit').value;
            const sourceSelect = [...document.querySelectorAll('#productUnitBody .unit-select')]
                .find(select => select.value === unitId);
            const source = sourceSelect?.closest('tr')?.querySelector('input[name*="[fixed_cost]"]');
            if (source) {
                row.querySelector('.dynamic-fixed-cost').value = formatMoney(numberValue(source.value));
            }
            calculate(row);
        }

        addButton.addEventListener('click', () => {
            const row = document.createElement('tr');
            row.innerHTML =
                '<td><select name="prices[' + priceIndex + '][price_mode_id]" class="form-control dynamic-mode" required>' +
                availablePriceModes.map(mode => '<option value="' + mode.id + '">' + mode.name + '</option>').join('') +
                '</select></td>' +
                '<td><select name="prices[' + priceIndex + '][unit_id]" class="form-control dynamic-unit" required></select></td>' +
                '<td><input type="text" name="prices[' + priceIndex + '][fixed_cost]" class="form-control dynamic-money dynamic-fixed-cost" value="0"></td>' +
                '<td><input type="text" name="prices[' + priceIndex + '][margin]" class="form-control dynamic-money dynamic-margin" value="0"></td>' +
                '<td><input type="text" name="prices[' + priceIndex + '][sale_price]" class="form-control dynamic-money dynamic-sale-price" value="0" readonly></td>' +
                '<td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-remove-price"><i class="feather-trash-2"></i></button></td>';
            body.appendChild(row);
            priceIndex++;
            refreshUnitOptions();
        });

        body.addEventListener('click', event => {
            const button = event.target.closest('.btn-remove-price');
            if (button) button.closest('tr').remove();
        });
        body.addEventListener('change', event => {
            if (event.target.matches('.dynamic-unit')) copyUnitFixedCost(event.target.closest('tr'));
        });
        body.addEventListener('input', event => {
            if (event.target.matches('.dynamic-fixed-cost, .dynamic-margin')) {
                event.target.value = formatMoney(numberValue(event.target.value));
                calculate(event.target.closest('tr'));
            }
        });
        document.addEventListener('change', event => {
            if (event.target.matches('#productUnitBody .unit-select')) refreshUnitOptions();
        });
        form.addEventListener('submit', () => {
            refreshUnitOptions();
            body.querySelectorAll('.dynamic-money').forEach(input => input.value = numberValue(input.value).toFixed(2));
        }, true);

        refreshUnitOptions();
        body.querySelectorAll('.dynamic-fixed-cost, .dynamic-margin').forEach(input => {
            input.value = formatMoney(numberValue(input.value));
        });
        body.querySelectorAll('tr').forEach(row => calculate(row));
    })();
</script>
