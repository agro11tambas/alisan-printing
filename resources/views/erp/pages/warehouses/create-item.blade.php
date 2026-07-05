@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Warehouse</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Warehouse</li>
            <li class="breadcrumb-item">Create Item</li>
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
                <a href="/erp/products/categories" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="warehouseForm">
                    <i class="feather-plus me-2"></i>
                    <span>Add Item</span>
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
@if(session('error'))
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
                <form action="/erp/warehouses/store" method="POST" id="warehouseForm" enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card-body">
                        <div class="row mb-2 align-items-center">
                            <div class="col-lg-2">
                                <label for="product" class="fw-semibold">Product:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <select class="form-control select-product" data-select2-selector="status" name="product" id="product">
                                        <option value="" disabled selected hidden>Pilih produk</option>
                                        @foreach ($products as $index => $product)
                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2 align-items-center">
                            <div class="col-lg-2">
                                <label for="stock" class="fw-semibold">Stock:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <select class="form-control form-select fs-13" id="stock" name="stock">
                                        <option value="">Pilih</option>
                                        <option value="masuk">Barang Masuk</option>
                                        <option value="keluar">Barang Keluar</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2 align-items-center d-none" id="form_barang_masuk">
                            <div class="col-lg-2">
                                <label for="barang_masuk" class="fw-semibold">Stock In:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-layers"></i></div>
                                    <input type="text" class="form-control" id="barang_masuk" name="barang_masuk" value="{{ old('barang_masuk') }}" placeholder="Stock In">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2 align-items-center d-none" id="form_barang_keluar">
                            <div class="col-lg-2">
                                <label for="barang_keluar" class="fw-semibold">Stock Out:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-layers"></i></div>
                                    <input type="text" class="form-control" id="barang_keluar" name="barang_keluar" value="{{ old('barang_keluar') }}" placeholder="Stock Out">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2 align-items-center">
                            <div class="col-lg-2">
                                <label for="date_change" class="fw-semibold">Date:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <input type="date" class="form-control" id="date_change" name="date_change" value="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row mb-2 align-items-center">
                            <div class="col-lg-2">
                                <label for="note" class="fw-semibold">Note:</label>
                            </div>
                            <div class="col-lg-10 mb-0">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-type"></i></div>
                                    <textarea name="note" id="note" cols="30" rows="5" placeholder="Note" class="form-control" value="{{ old('note') }}"></textarea>
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
    document.addEventListener('DOMContentLoaded', function() {
        const selectTipe = document.getElementById('stock');
        const formMasuk = document.getElementById('form_barang_masuk');
        const formKeluar = document.getElementById('form_barang_keluar');

        selectTipe.addEventListener('change', function() {
            const value = this.value;

            formMasuk.classList.add('d-none');
            formKeluar.classList.add('d-none');

            if (value === 'masuk') {
                formMasuk.classList.remove('d-none');
            } else if (value === 'keluar') {
                formKeluar.classList.remove('d-none');
            }
        });
    });

    document.getElementById('warehouseForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        const rules = [{
                selector: 'input[name="date_change"]',
                message: 'Tanggal wajib diisi'
            },
        ];

        let isValid = true;

        rules.forEach(rule => {
            const el = form.querySelector(rule.selector);
            const val = el?.value ?? '';
            const valid = rule.validate ? rule.validate(val) : val.trim() !== '';

            if (!valid) {
                showError(el, rule.message);
                isValid = false;
            }
        });

        if (isValid) form.submit();
    });

    function showError(input, message) {
        if (!input) return;
        input.classList.add('is-invalid');
        const parent = input.closest('div');
        if (!parent) return;
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        feedback.textContent = message;
        parent.appendChild(feedback);
    }
</script>
@endpush
