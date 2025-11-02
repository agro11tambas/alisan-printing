@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock Opname Production</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Stock Opname</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2">
                    <a href="/erp/productions/stock-opname" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-primary" form="stockOpnameForm">
                        <i class="feather-plus me-2"></i>Create Stock Opname
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content">
        <div class="card stretch">
            <form action="/erp/productions/stock-opname/store" method="POST" id="stockOpnameForm">
                @csrf
                <div class="card-body">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Available Quantity</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Notes</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr>
                                <td>
                                    <select name="items[0][product_id]" class="form-select select2-product">
                                        <option value="" disabled selected hidden>Select product</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}">[{{ $p->sku }}] {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="items[0][production_warehouse_id]" value="2">
                                </td>
                                <td>
                                    <input type="text" inputmode="numeric" name="items[0][available_quantity]"
                                        class="form-control available_quantity" placeholder="Available Quantity">
                                </td>
                                <td>
                                    <select name="items[0][status]" class="form-select" data-select2-selector="tag">
                                        <option value="Gain" data-bg="bg-success">Gain</option>
                                        <option value="Loss" data-bg="bg-danger">Loss</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="date" name="items[0][date]" value="{{ date('Y-m-d') }}"
                                        class="form-control">
                                </td>
                                <td>
                                    <input type="text" name="items[0][notes]" class="form-control" placeholder="Notes">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm removeRow">
                                        <i class="feather-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <template id="rowTemplate">
                        <tr>
                            <td>
                                <select class="form-select select2-product">
                                    <option value="" disabled selected hidden>Select product</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}">[{{ $p->sku }}] {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" value="2" class="warehouse-id">
                            </td>
                            <td>
                                <input type="text" inputmode="numeric" class="form-control available_quantity"
                                    placeholder="Available Quantity">
                            </td>
                            <td>
                                <select class="form-select status" data-select2-selector="tag">
                                    <option value="Gain" data-bg="bg-success">Gain</option>
                                    <option value="Loss" data-bg="bg-danger">Loss</option>
                                </select>
                            </td>
                            <td><input type="date" value="{{ date('Y-m-d') }}" class="form-control date"></td>
                            <td><input type="text" class="form-control notes" placeholder="Notes"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm removeRow"><i
                                        class="feather-trash-2"></i></button>
                            </td>
                        </tr>
                    </template>

                    <button type="button" class="btn btn-outline-primary" id="addRowBtn">
                        <i class="feather-plus"></i> Add Row
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function numberFormat(num) {
            if (!num) return '';
            return new Intl.NumberFormat('id-ID').format(num);
        }

        function initSelect2(scope) {
            $(scope).find('.select2-product').each(function() {
                const $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) return;
                $el.select2({
                    width: '100%',
                    dropdownParent: $(document.body)
                });
            });

            $(scope).find('select[data-select2-selector="tag"]').each(function() {
                const $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) return;
                $el.select2({
                    width: '100%',
                    dropdownParent: $(document.body),
                    minimumResultsForSearch: Infinity,
                    templateResult: formatStatusOption,
                    templateSelection: formatStatusOption
                });
            });
        }

        function formatStatusOption(state) {
            if (!state.id) return state.text;
            const bgClass = $(state.element).data('bg');
            const color = bgClass === 'bg-success' ? '#16a34a' : '#dc2626';
            return $('<span style="display:flex;align-items:center;gap:8px">' +
                '<span style="width:7px;height:7px;border-radius:50%;background-color:' + color + '"></span>' +
                '<span>' + state.text + '</span></span>');
        }

        $(document).ready(function() {
            initSelect2(document);

            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });

            // Format angka input
            $(document).on('input', 'input[name^="items"][name$="[available_quantity]"]', function() {
                let raw = this.value.replace(/\D/g, '');
                this.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
            });

            let rowIndex = 1;
            $('#addRowBtn').on('click', function() {
                const tmpl = document.getElementById('rowTemplate');
                const clone = tmpl.content.cloneNode(true);
                const $row = $(clone).find('tr');

                $row.find('.select2-product').attr('name', `items[${rowIndex}][product_id]`);
                $row.find('.warehouse-id').attr('name', `items[${rowIndex}][production_warehouse_id]`);
                $row.find('.available_quantity').attr('name', `items[${rowIndex}][available_quantity]`);
                $row.find('.status').attr('name', `items[${rowIndex}][status]`);
                $row.find('.date').attr('name', `items[${rowIndex}][date]`);
                $row.find('.notes').attr('name', `items[${rowIndex}][notes]`);

                $('#itemsBody').append($row);
                initSelect2($row);
                rowIndex++;
            });

            $(document).on('click', '.removeRow', function() {
                $(this).closest('tr').remove();
            });

            const form = document.getElementById('stockOpnameForm');

            form.addEventListener('submit', function(e) {
                let isValid = true;
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const rows = form.querySelectorAll('#itemsBody tr');
                rows.forEach((row, i) => {
                    const product = row.querySelector('select.select2-product');
                    const qty = row.querySelector(
                        'input[name^="items"][name$="[available_quantity]"]');
                    const date = row.querySelector('input[type="date"]');
                    const numericQty = parseFloat(qty.value.replace(/\./g, '')) || 0;

                    if (!product.value) {
                        isValid = false;
                        showError(product, `Produk baris ${i + 1} wajib dipilih`);
                    }
                    if (numericQty <= 0) {
                        isValid = false;
                        showError(qty, 'Available quantity minimal 1');
                    }
                    if (!date.value.trim()) {
                        isValid = false;
                        showError(date, 'Tanggal wajib diisi');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                // bersihkan format ribuan sebelum submit
                form.querySelectorAll('input[name^="items"][name$="[available_quantity]"]').forEach(
                input => {
                    input.value = input.value.replace(/\./g, '');
                });
            });

            function showError(el, message) {
                if ($(el).hasClass('select2-hidden-accessible')) {
                    const select2Container = $(el).next('.select2');
                    select2Container.next('.invalid-feedback').remove();
                    const feedback = $('<div class="invalid-feedback d-block">' + message + '</div>');
                    select2Container.after(feedback);
                } else {
                    el.classList.add('is-invalid');
                    const parent = el.closest('.input-group') || el.parentNode;
                    const existing = parent.querySelector('.invalid-feedback');
                    if (existing) existing.remove();
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block';
                    feedback.textContent = message;
                    parent.appendChild(feedback);
                }
            }

            form.querySelectorAll('input, select').forEach(el => {
                el.addEventListener('input', () => {
                    el.classList.remove('is-invalid');
                    const next = el.parentNode.querySelector('.invalid-feedback');
                    if (next) next.remove();
                });
            });
        });
    </script>
@endpush
