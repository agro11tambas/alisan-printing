@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock Opname</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Stock Opname</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/inventory/stock-opname" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="stockOpnameForm">
                        <i class="feather-plus me-2"></i>
                        <span>Edit Stock Opname</span>
                    </button>
                </div>
            </div>
            <div class="d-md-none d-flex align-items-center">
                <a href="javascript:void(0)" class="page-header-right-open-toggle">
                    <i class="feather-align-right fs-20"></i>
                </a>
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
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/inventory/stock-opname/update/{{ $stockOpname->id }}" method="POST"
                        id="stockOpnameForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <input type="hidden" name="inventory_warehouse_id" id="inventory_warehouse_id" value="1">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="product" class="fw-semibold">Product:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-control" data-select2-selector="status" id="product"
                                        name="product">
                                        <option value="" disabled selected hidden>Pilih produk</option>
                                        @foreach ($products as $index => $product)
                                            <option value="{{ $product->id }}"
                                                {{ $product->id == $stockOpname->product_id ? 'selected' : '' }}>
                                                [{{ $product->sku }}] {{ $product->name }}</option>
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
                                            value="{{ old('date', isset($order->date) ? \Carbon\Carbon::parse($order->date)->format('Y-m-d') : date('Y-m-d')) }}"
                                            placeholder="date">
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
                                        <input type="number" class="form-control" id="quantity" name="quantity"
                                            value="{{ old('quantity', $stockOpname->quantity) }}" placeholder="Quantity">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="status" class="fw-semibold">Status:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-control" data-select2-selector="status" id="status"
                                        name="status">
                                        <option value="Gain" data-bg="bg-success"
                                            {{ $stockOpname->status == 'Gain' ? 'selected' : '' }}>Gain</option>
                                        <option value="Loss" data-bg="bg-danger"
                                            {{ $stockOpname->status == 'Loss' ? 'selected' : '' }}>Loss</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="notes" class="fw-semibold">Notes:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-box"></i></div>
                                        <input type="text" class="form-control" id="notes" name="notes"
                                            value="{{ old('notes', $stockOpname->notes) }}" placeholder="Notes">
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
        // === INIT SELECT2 ===
        function initSelect2(el) {
            $(el).select2({
                width: '100%',
                placeholder: 'Pilih produk',
                dropdownParent: $(document.body)
            });
        }

        $(document).ready(function() {
            initSelect2('#product');
            initSelect2('#status');

            // Autofocus search saat Select2 dibuka
            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });
        });

        // === VALIDASI FRONTEND ===
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('stockOpnameForm');

            form.addEventListener('submit', function(e) {
                let isValid = true;

                // Hapus error lama
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const product = $('#product');
                const date = form.querySelector('input[name="date"]');
                const quantity = form.querySelector('input[name="quantity"]');

                // Product wajib
                if (!product.val()) {
                    isValid = false;
                    showError(product[0], 'Produk wajib dipilih');
                }

                // Date wajib
                if (!date.value.trim()) {
                    isValid = false;
                    showError(date, 'Tanggal wajib diisi');
                }

                // Quantity wajib & minimal 1
                if (!quantity.value.trim() || parseFloat(quantity.value) <= 0) {
                    isValid = false;
                    showError(quantity, 'Quantity minimal 1');
                }

                if (!isValid) e.preventDefault();
            });

            // Fungsi tampilkan error di bawah field
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

            // Hapus error otomatis ketika user ubah input
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
