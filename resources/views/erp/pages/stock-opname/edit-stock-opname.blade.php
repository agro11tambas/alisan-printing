@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Edit Stock Opname</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Stock Opname</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2">
                    <a href="/erp/inventory/stock-opname" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-primary" form="stockOpnameForm">
                        <i class="feather-save me-2"></i>Save Changes
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
                    <form action="/erp/inventory/stock-opname/update/{{ $stockOpname->id }}" method="POST"
                        id="stockOpnameForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <input type="hidden" name="inventory_warehouse_id" value="1">

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="product" class="fw-semibold">Product:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-control select2-product" id="product" name="product">
                                        <option value="" disabled selected hidden>Pilih produk</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                {{ $product->id == $stockOpname->product_id ? 'selected' : '' }}>
                                                [{{ $product->sku }}] {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="date" class="fw-semibold">Date:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-calendar"></i></div>
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="{{ old('date', \Carbon\Carbon::parse($stockOpname->date)->format('Y-m-d')) }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="quantity" class="fw-semibold">Quantity:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-box"></i></div>
                                        <input type="text" inputmode="numeric" class="form-control" id="quantity"
                                            name="quantity"
                                            value="{{ number_format(old('quantity', $stockOpname->quantity), 0, ',', '.') }}"
                                            placeholder="Masukkan jumlah">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="status" class="fw-semibold">Status:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-control select2-status" id="status" name="status"
                                        data-select2-selector="tag">
                                        <option value="Gain" {{ $stockOpname->status == 'Gain' ? 'selected' : '' }}
                                            data-bg="bg-success">Gain
                                        </option>
                                        <option value="Loss" {{ $stockOpname->status == 'Loss' ? 'selected' : '' }}
                                            data-bg="bg-danger">Loss
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="notes" class="fw-semibold">Notes:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-edit"></i></div>
                                        <input type="text" class="form-control" id="notes" name="notes"
                                            value="{{ old('notes', $stockOpname->notes) }}"
                                            placeholder="Catatan (optional)">
                                    </div>
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
        const formatID = new Intl.NumberFormat('id-ID');

        function formatNumberInput(el) {
            let raw = el.value.replace(/\D/g, '');
            el.value = raw ? formatID.format(raw) : '';
        }

        function parseNumberValue(str) {
            return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
        }

        function initSelect2(el) {
            $(el).select2({
                width: '100%',
                placeholder: 'Pilih opsi',
                dropdownParent: $(document.body)
            });
        }

        $(document).ready(function() {
            initSelect2('#product');
            initSelect2('#status');

            $(document).on('input', '#quantity', function() {
                formatNumberInput(this);
            });

            $('#stockOpnameForm').on('submit', function(e) {
                const qty = document.getElementById('quantity');
                qty.value = parseNumberValue(qty.value);
            });

            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('stockOpnameForm');

            form.addEventListener('submit', function(e) {
                let isValid = true;

                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const product = $('#product');
                const date = form.querySelector('input[name="date"]');
                const quantity = form.querySelector('input[name="quantity"]');

                const numericQty = parseNumberValue(quantity.value);

                if (!product.val()) {
                    isValid = false;
                    showError(product[0], 'Produk wajib dipilih');
                }
                if (!date.value.trim()) {
                    isValid = false;
                    showError(date, 'Tanggal wajib diisi');
                }
                if (numericQty <= 0) {
                    isValid = false;
                    showError(quantity, 'Quantity minimal 1');
                }

                if (!isValid) e.preventDefault();
            });

            function showError(el, message) {
                if ($(el).hasClass('select2-hidden-accessible')) {
                    const select2Container = $(el).next('.select2');
                    select2Container.next('.invalid-feedback').remove();
                    const feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback d-block';
                    feedback.textContent = message;
                    select2Container[0].after(feedback);
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
