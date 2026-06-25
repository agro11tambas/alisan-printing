@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Products</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Products</li>
                <li class="breadcrumb-item">Create Product Bundles</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/products/product-bundles" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-primary" form="productBundleForm">
                        <i class="feather-plus me-2"></i>Add Product Bundle
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}",
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/products/product-bundles/store" method="POST" id="productBundleForm">
                        @csrf
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-lg-12">
                                    <label class="fw-semibold mb-2">Pilih Produk untuk Bundle:</label>

                                    <table class="table table-bordered align-middle" id="productTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Produk</th>
                                                <th width="100" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="productBody">
                                            <tr>
                                                <td>1</td>
                                                <td>
                                                    <select class="form-select product-select" name="products[]"
                                                        data-select2-selector="tag">
                                                        <option value="" disabled selected hidden>Pilih produk
                                                        </option>
                                                        @foreach ($products as $product)
                                                            <option value="{{ $product->id }}"
                                                                data-name="{{ $product->name }}">
                                                                {{ $product->name }} - {{ $product->sku }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm removeRow">
                                                        <i class="feather-trash-2"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <button type="button" class="btn btn-outline-primary" id="addRowBtn">
                                        <i class="feather-plus"></i> Tambah Produk
                                    </button>

                                    <small class="text-muted d-block mt-2">
                                        Pilih minimal dua produk untuk membuat bundle
                                    </small>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="text" class="form-control" id="name" name="name" readonly>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="sku" class="fw-semibold">SKU</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="text" class="form-control" id="sku" name="sku"
                                        value="{{ old('sku') }}" placeholder="SKU">
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="base_unit_id" class="fw-semibold">Base Unit</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-control" id="base_unit_id" name="base_unit_id">
                                        <option value="">Choose Base Unit</option>
                                        @foreach ($productUnits as $unit)
                                            <option value="{{ $unit->id }}"
                                                {{ old('base_unit_id') == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="price" class="fw-semibold">Price</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="text" inputmode="numeric" class="form-control" id="price"
                                        name="price" value="{{ old('price') }}" placeholder="Price">
                                </div>
                            </div> --}}

                            <div class="row mb-3 align-items-start">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Product Units</label>
                                </div>

                                <div class="col-lg-10 mb-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle" id="bundleUnitTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 30%">Unit</th>
                                                    <th style="width: 25%">Conversion</th>
                                                    <th style="width: 30%">Sale Price</th>
                                                    <th style="width: 10%" class="text-center">Aksi</th>
                                                </tr>
                                            </thead>

                                            <tbody id="bundleUnitBody">
                                                <tr>
                                                    <td>
                                                        <select name="units[0][unit_id]"
                                                            class="form-control bundle-unit-select">
                                                            <option value="">Pilih unit</option>
                                                            @foreach ($productUnits as $unit)
                                                                <option value="{{ $unit->id }}">{{ $unit->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>

                                                    <td>
                                                        <input type="text" name="units[0][conversion_value]"
                                                            class="form-control conversion-input" inputmode="decimal"
                                                            placeholder="Contoh: 1 / 10 / 100">
                                                    </td>

                                                    <td>
                                                        <input type="text" name="units[0][sale_price]"
                                                            class="form-control unit-money-field" inputmode="numeric"
                                                            placeholder="0">
                                                    </td>

                                                    <td class="text-center">
                                                        <button type="button"
                                                            class="btn btn-danger btn-sm removeUnitRow">
                                                            <i class="feather-trash-2"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <button type="button" class="btn btn-outline-primary btn-sm" id="addUnitRowBtn">
                                        <i class="feather-plus"></i> Tambah Unit
                                    </button>

                                    <small class="text-muted d-block mt-2">
                                        Contoh: Paket = 1, Dus = 10. Jika jual 2 Dus, sistem membaca 20 bundle.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $.fn.dataTable = function() {
            return this;
        };

        $(document).ready(function() {
            let rowIndex = 1;
            let unitRowIndex = 1;

            function initSelect2(el) {
                $(el).select2({
                    placeholder: 'Pilih produk',
                    width: '100%',
                    dropdownParent: $('#productBundleForm'),
                    matcher: function(params, data) {
                        if ($.trim(params.term) === '') return data;
                        if (data.text.toLowerCase().includes(params.term.toLowerCase())) return data;
                        return null;
                    }
                });
            }

            initSelect2($('.product-select'));

            $('#addRowBtn').on('click', function() {
                rowIndex++;
                const newRow = `
            <tr>
                <td>${rowIndex}</td>
                <td>
                    <select class="form-select product-select" name="products[]" data-select2-selector="tag">
                        <option value="" disabled selected hidden>Pilih produk</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" data-name="{{ $product->name }}">
                                {{ $product->name }} - {{ $product->sku }}
                            </option>
                        @endforeach
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm removeRow">
                        <i class="feather-trash-2"></i>
                    </button>
                </td>
            </tr>`;
                $('#productBody').append(newRow);
                initSelect2($('#productBody tr:last .product-select'));
                refreshDropdownOptions();
            });

            $(document).on('click', '.removeRow', function() {
                $(this).closest('tr').remove();
                updateRowNumbers();
                updateBundleName();
                refreshDropdownOptions();
            });

            $(document).on('change', '.product-select', function() {
                updateBundleName();
                refreshDropdownOptions();
            });

            function updateRowNumbers() {
                $('#productBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }

            function updateBundleName() {
                let names = [];
                $('.product-select').each(function() {
                    const selected = $(this).find('option:selected').data('name');
                    if (selected) names.push(selected);
                });
                $('#name').val(names.join(' + '));
            }

            function refreshDropdownOptions() {
                // ambil semua produk yang sudah dipilih
                const selectedProducts = $('.product-select').map(function() {
                    return $(this).val();
                }).get().filter(Boolean);

                // untuk setiap select
                $('.product-select').each(function() {
                    const currentSelect = $(this);
                    const currentValue = currentSelect.val(); // produk yang sedang aktif

                    // Simpan value yang sekarang supaya tidak hilang
                    currentSelect.find('option').each(function() {
                        const val = $(this).attr('value');
                        if (!val) return;

                        // kalau produk sudah dipilih di select lain, hapus dari dropdown
                        if (selectedProducts.includes(val) && val !== currentValue) {
                            $(this).remove(); // Hapus option
                        } else {
                            // kalau option hilang tapi sekarang dibutuhkan lagi → tambahkan kembali
                            const exists = currentSelect.find('option[value="' + val + '"]').length;
                            if (!exists && (val === currentValue || !selectedProducts.includes(
                                    val))) {
                                // tambahkan kembali option (jaga-jaga kalau sebelumnya kehapus)
                                const original = $(`#productTable option[value="${val}"]:first`)
                                    .clone();
                                if (original.length) {
                                    currentSelect.append(original);
                                }
                            }
                        }
                    });

                    currentSelect.trigger('change.select2');
                });
            }

            $('#productBundleForm').on('submit', function(e) {
                let productCount = $('.product-select').filter(function() {
                    return $(this).val() !== null && $(this).val() !== '';
                }).length;

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let isValid = true;

                if (productCount < 2) {
                    isValid = false;
                    const feedback =
                        `<div class="invalid-feedback d-block">Minimal pilih 2 produk untuk membuat bundle.</div>`;
                    $('#productTable').after(feedback);
                }

                if (!$('#sku').val().trim()) {
                    isValid = false;
                    showError($('#sku')[0], 'SKU wajib diisi');
                }

                if (!$('#price').val().trim()) {
                    isValid = false;
                    showError($('#price')[0], 'Harga wajib diisi');
                }

                let selectedUnits = [];

                $('#bundleUnitBody tr').each(function() {
                    const unitSelect = $(this).find('.bundle-unit-select');
                    const conversionInput = $(this).find('.conversion-input');
                    const salePriceInput = $(this).find('.unit-money-field');

                    const unitId = unitSelect.val();
                    const conversion = conversionInput.val();
                    const salePrice = salePriceInput.val();

                    const rowEmpty =
                        (!unitId || unitId === '') &&
                        (!conversion || conversion.trim() === '') &&
                        (!salePrice || salePrice.trim() === '');

                    if (rowEmpty) {
                        return;
                    }

                    if (!unitId) {
                        isValid = false;
                        showError(unitSelect[0], 'Unit wajib dipilih');
                    }

                    if (!conversion || parseFloat(conversion) <= 0) {
                        isValid = false;
                        showError(conversionInput[0], 'Conversion wajib lebih dari 0');
                    }

                    if (!salePrice || salePrice.trim() === '') {
                        isValid = false;
                        showError(salePriceInput[0], 'Sale price wajib diisi');
                    }

                    if (unitId) {
                        if (selectedUnits.includes(unitId)) {
                            isValid = false;
                            showError(unitSelect[0], 'Unit tidak boleh duplikat');
                        }

                        selectedUnits.push(unitId);
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                $('.unit-money-field').each(function() {
                    const raw = $(this).val().replace(/\./g, '').replace(',', '.');
                    $(this).val(raw || '0');
                });

                $('.conversion-input').each(function() {
                    const raw = $(this).val().replace(',', '.');
                    $(this).val(raw);
                });
            });

            function showError(input, message) {
                input.classList.add('is-invalid');
                const parent = input.closest('div');
                if (!parent) return;
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                parent.appendChild(feedback);
            }

            // 🧩 Tambahkan di dalam $(document).ready(function() { ... })

            // --- fungsi format & unformat (Indonesia → en-US) ---
            function formatRibuan(value) {
                if (value === null || value === undefined || value === '') return '';
                value = value.toString().replace(/[^0-9,]/g, ''); // hanya angka dan koma
                let [intPart, decPart] = value.split(',');
                intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return decPart !== undefined ? `${intPart},${decPart}` : intPart;
            }

            function unformatRibuan(value) {
                if (!value) return 0;
                // ubah "1.234,56" → "1234.56"
                return value.toString().replace(/\./g, '').replace(',', '.');
            }


            // --- format realtime di field price ---
            $('#price').on('input', function() {
                let val = $(this).val();

                // izinkan angka dan koma
                val = val.replace(/[^0-9,]/g, '');

                // format ribuan (pakai titik)
                $(this).val(formatRibuan(val));
            });

            // --- sebelum form disubmit, ubah ke format en-US ---
            $('#productBundleForm').on('submit', function() {
                const val = $('#price').val();
                const enValue = unformatRibuan(val);
                $('#price').val(enValue);
            });


            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });

            $('#addUnitRowBtn').on('click', function() {
                const row = `
        <tr>
            <td>
                <select name="units[${unitRowIndex}][unit_id]" class="form-control bundle-unit-select">
                    <option value="">Pilih unit</option>
                    @foreach ($productUnits as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </td>

            <td>
                <input type="text"
                    name="units[${unitRowIndex}][conversion_value]"
                    class="form-control conversion-input"
                    inputmode="decimal"
                    placeholder="Contoh: 1 / 10 / 100">
            </td>

            <td>
                <input type="text"
                    name="units[${unitRowIndex}][sale_price]"
                    class="form-control unit-money-field"
                    inputmode="numeric"
                    placeholder="0">
            </td>

            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm removeUnitRow">
                    <i class="feather-trash-2"></i>
                </button>
            </td>
        </tr>
    `;

                $('#bundleUnitBody').append(row);
                unitRowIndex++;
            });

            $(document).on('click', '.removeUnitRow', function() {
                $(this).closest('tr').remove();
            });

            $(document).on('input', '.conversion-input', function() {
                let val = $(this).val().replace(/[^0-9.,]/g, '');
                val = val.replace(',', '.');

                $(this).val(val);
            });

            $(document).on('input', '.unit-money-field', function() {
                let raw = $(this).val().replace(/\D/g, '');

                if (!raw) {
                    $(this).val('');
                    return;
                }

                $(this).val(raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
            });
        });
    </script>
@endpush
