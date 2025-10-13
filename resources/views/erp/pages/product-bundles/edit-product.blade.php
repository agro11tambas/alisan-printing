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
                <li class="breadcrumb-item">Edit Product Bundle</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/products/product-bundles" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-primary" form="productBundleForm">
                        <i class="feather-check me-2"></i>Update Product Bundle
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/products/product-bundles/update/{{ $bundle->id }}" method="POST"
                        id="productBundleForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">

                            {{-- 🔹 Produk Bundle --}}
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
                                            @foreach ($bundle->items as $index => $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <select class="form-select product-select" name="products[]"
                                                            data-select2-selector="tag">
                                                            <option value="" disabled hidden>Pilih produk</option>
                                                            @foreach ($products as $product)
                                                                <option value="{{ $product->id }}"
                                                                    data-name="{{ $product->name }}"
                                                                    {{ $product->id == $item->product_id ? 'selected' : '' }}>
                                                                    {{ $product->name }} - {{ $product->sku }}
                                                                    (Rp{{ number_format($product->price) }})
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
                                            @endforeach
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

                            {{-- 🔹 Nama Bundle (Auto) --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name', $bundle->name) }}" readonly>
                                </div>
                            </div>

                            {{-- 🔹 SKU --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="sku" class="fw-semibold">SKU</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="text" class="form-control" id="sku" name="sku"
                                        value="{{ old('sku', $bundle->sku) }}" placeholder="SKU">
                                </div>
                            </div>

                            {{-- 🔹 Harga --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="price" class="fw-semibold">Price</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="number" class="form-control" id="price" name="price"
                                        value="{{ old('price', $bundle->price) }}" placeholder="Price">
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
        $(document).ready(function() {
            let rowIndex = $('#productBody tr').length;

            // ✅ init select2 for all product-select
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

            // Inisialisasi awal
            initSelect2($('.product-select'));
            updateBundleName();

            // 🔹 Tambah produk baru
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
                                {{ $product->name }} - {{ $product->sku }} (Rp{{ number_format($product->price) }})
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
            });

            // 🔹 Hapus produk
            $(document).on('click', '.removeRow', function() {
                $(this).closest('tr').remove();
                updateRowNumbers();
                updateBundleName();
            });

            // 🔹 Update nama bundle otomatis dari pilihan produk
            $(document).on('change', '.product-select', function() {
                updateBundleName();
            });

            // 🔹 Re-index nomor urut
            function updateRowNumbers() {
                $('#productBody tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            }

            // 🔹 Generate nama bundle otomatis
            function updateBundleName() {
                let names = [];
                $('.product-select').each(function() {
                    const selected = $(this).find('option:selected').data('name');
                    if (selected) names.push(selected);
                });
                $('#name').val(names.join(' + '));
            }

            // 🔹 Validasi form sebelum submit
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

                if (!isValid) e.preventDefault();
            });

            // 🔹 Fungsi tampilkan error
            function showError(input, message) {
                input.classList.add('is-invalid');
                const parent = input.closest('div');
                if (!parent) return;
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                parent.appendChild(feedback);
            }

            // ✅ Auto-focus ke search box saat select2 dibuka
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });
        });
    </script>
@endpush
