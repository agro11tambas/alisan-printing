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

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/products/product-bundles/store" method="POST" id="productBundleForm">
                        @csrf
                        <div class="card-body">
                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Product Primary</label>
                                </div>
                                <div class="col-lg-10">
                                    <select class="form-select product-select" id="primary_product_id"
                                        name="primary_product_id" data-select2-selector="tag">
                                        <option value="">Pilih primary product</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">
                                                {{ $product->name }} - {{ $product->sku }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Product Secondary</label>
                                </div>

                                <div class="col-lg-10">
                                    <table class="table table-bordered align-middle" id="secondaryProductTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="50">#</th>
                                                <th>Secondary Product</th>
                                                <th width="100" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>

                                        <tbody id="secondaryProductBody">
                                            <tr>
                                                <td>1</td>
                                                <td>
                                                    <select class="form-select secondary-product-select"
                                                        name="secondary_product_ids[]" data-select2-selector="tag">
                                                        {{-- Diisi refreshSecondaryOptions() dari productOptionsHtml. --}}
                                                        <option value="" disabled selected hidden>Pilih secondary
                                                            product</option>
                                                    </select>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger btn-sm removeSecondaryRow">
                                                        <i class="feather-trash-2"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>


                                    <div class="row mt-2">
                                        <div class="col-lg-6">
                                            <label class="fw-semibold">Preview Name</label>
                                            <textarea class="form-control" id="preview_name" rows="4" readonly></textarea>
                                        </div>

                                        <div class="col-lg-6">
                                            <label class="fw-semibold">Preview SKU</label>
                                            <textarea class="form-control" id="preview_sku" rows="4" readonly></textarea>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-outline-primary btn-sm mt-2"
                                        id="addSecondaryRowBtn">
                                        <i class="feather-plus"></i> Tambah Secondary
                                    </button>

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
        const existingBundles = @json($existingBundles);

        $(document).ready(function() {
            let secondaryRowIndex = 1;

            function initSelect2(el) {
                $(el).select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih produk',
                    width: '100%',
                    dropdownParent: $('#productBundleForm'),
                    minimumResultsForSearch: 0,
                    matcher: function(params, data) {
                        if ($.trim(params.term) === '') return data;
                        if (data.text.toLowerCase().includes(params.term.toLowerCase())) return data;
                        return null;
                    }
                });
            }

            const productOptionsHtml = `
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">
                        {{ $product->name }} - {{ $product->sku }}
                    </option>
                @endforeach
            `;

            function refreshSecondaryOptions() {
                const primaryId = $('#primary_product_id').val();

                const selectedSecondaryIds = $('.secondary-product-select').map(function() {
                    return $(this).val();
                }).get().filter(Boolean);

                $('.secondary-product-select').each(function() {
                    const currentSelect = $(this);
                    const currentValue = currentSelect.val();

                    currentSelect.empty();

                    currentSelect.append(`
            <option value="" disabled selected hidden>Pilih secondary product</option>
        `);

                    const tempOptions = $('<select>' + productOptionsHtml + '</select>').find('option');

                    tempOptions.each(function() {
                        const optionValue = $(this).val();

                        let shouldRemove = false;

                        if (optionValue == primaryId) {
                            shouldRemove = true;
                        }

                        if (selectedSecondaryIds.includes(optionValue) && optionValue !=
                            currentValue) {
                            shouldRemove = true;
                        }

                        if (!shouldRemove) {
                            shouldRemove = existingBundles.some(bundle =>
                                bundle.primary == primaryId &&
                                bundle.secondary == optionValue
                            );
                        }

                        if (!shouldRemove) {
                            currentSelect.append($(this).clone());
                        }
                    });

                    currentSelect.val(currentValue);

                    if (!currentSelect.find(`option[value="${currentValue}"]`).length) {
                        currentSelect.val('');
                    }

                    currentSelect.trigger('change.select2');
                });
            }

            function toggleSecondaryState() {
                const hasPrimary = !!$('#primary_product_id').val();

                $('.secondary-product-select').prop('disabled', !hasPrimary);

                $('#addSecondaryRowBtn').prop('disabled', !hasPrimary);

                if (!hasPrimary) {
                    $('.secondary-product-select').val(null).trigger('change');
                    $('#preview_name').val('');
                    $('#preview_sku').val('');
                }
            }

            initSelect2($('#primary_product_id'));
            initSelect2($('.secondary-product-select'));
            toggleSecondaryState();
            refreshSecondaryOptions();

            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });

            $(document).on('change', '#primary_product_id', function() {
                toggleSecondaryState();
                refreshSecondaryOptions();
                updatePreviewBundle();
            });

            $(document).on('change', '.secondary-product-select', function() {
                refreshSecondaryOptions();
                updatePreviewBundle();
            });

            function updatePreviewBundle() {
                const primaryOption = $('#primary_product_id option:selected');

                const primaryName = primaryOption.text().trim();
                const primarySku = primaryOption.text().split(' - ').pop().trim();

                let names = [];
                let skus = [];

                if (!$('#primary_product_id').val()) {
                    $('#preview_name').val('');
                    $('#preview_sku').val('');
                    return;
                }

                $('.secondary-product-select').each(function() {
                    const secondaryOption = $(this).find('option:selected');

                    if (!$(this).val()) return;

                    const secondaryText = secondaryOption.text().trim();
                    const secondarySku = secondaryText.split(' - ').pop().trim();

                    names.push(primaryName + ' + ' + secondaryText);
                    skus.push(primarySku + secondarySku);
                });

                $('#preview_name').val(names.join('\n'));
                $('#preview_sku').val(skus.join('\n'));
            }

            $('#addSecondaryRowBtn').on('click', function() {
                secondaryRowIndex++;

                const row = `
                <tr>
                    <td>${secondaryRowIndex}</td>
                    <td>
                        <select class="form-select secondary-product-select"
                            name="secondary_product_ids[]"
                            data-select2-selector="tag">
                            <!-- Diisi refreshSecondaryOptions() yang dipanggil tepat setelah baris ini ditambahkan. -->
                            <option value="" disabled selected hidden>Pilih secondary product</option>
                        </select>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm removeSecondaryRow">
                            <i class="feather-trash-2"></i>
                        </button>
                    </td>
                </tr>
            `;

                $('#secondaryProductBody').append(row);
                initSelect2($('#secondaryProductBody tr:last .secondary-product-select'));
                $('#secondaryProductBody tr:last .secondary-product-select')
                    .prop('disabled', !$('#primary_product_id').val());
                refreshSecondaryOptions();
                updatePreviewBundle();

            });

            $(document).on('click', '.removeSecondaryRow', function() {
                $(this).closest('tr').remove();
                updateSecondaryRowNumbers();
                updatePreviewBundle();
                refreshSecondaryOptions();
            });

            function updateSecondaryRowNumbers() {
                $('#secondaryProductBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }

            $('#productBundleForm').on('submit', function(e) {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let isValid = true;

                const primaryId = $('#primary_product_id').val();

                const secondaryIds = $('.secondary-product-select').map(function() {
                    return $(this).val();
                }).get().filter(Boolean);

                if (!primaryId) {
                    isValid = false;
                    showError($('#primary_product_id')[0], 'Primary product wajib dipilih');
                }

                if (secondaryIds.length < 1) {
                    isValid = false;
                    $('#secondaryProductTable').after(
                        `<div class="invalid-feedback d-block">Minimal pilih 1 secondary product.</div>`
                    );
                }

                if (secondaryIds.includes(primaryId)) {
                    isValid = false;
                    $('#secondaryProductTable').after(
                        `<div class="invalid-feedback d-block">Secondary tidak boleh sama dengan primary.</div>`
                    );
                }

                if (secondaryIds.length !== [...new Set(secondaryIds)].length) {
                    isValid = false;
                    $('#secondaryProductTable').after(
                        `<div class="invalid-feedback d-block">Secondary product tidak boleh duplikat.</div>`
                    );
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });

            function showError(input, message) {
                input.classList.add('is-invalid');

                const parent = input.closest('.col-lg-10') || input.parentElement;
                if (!parent) return;

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                feedback.textContent = message;

                parent.appendChild(feedback);
            }
        });
    </script>
@endpush
