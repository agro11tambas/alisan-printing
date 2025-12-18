@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Edit Stock Opname</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Stock Opname</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                <a href="/erp/productions/stock-opname" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="stockOpnameForm">
                    <i class="feather-save me-2"></i>
                    <span>Update Stock Opname</span>
                </button>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/productions/stock-opname/update/{{ $stockOpname->id }}" method="POST"
                        id="stockOpnameForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <input type="hidden" name="production_warehouse_id"
                                value="{{ $stockOpname->production_warehouse_id ?? 1 }}">

                            {{-- Product --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="product" class="fw-semibold">Product:</label>
                                </div>
                                <div class="col-lg-10">
                                    <select class="form-control" id="product" name="product">
                                        <option value="" disabled hidden>Pilih produk</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}"
                                                {{ $stockOpname->product_id == $product->id ? 'selected' : '' }}>
                                                [{{ $product->sku }}] {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Date --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="date" class="fw-semibold">Date:</label>
                                </div>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-calendar"></i></div>
                                        <input type="date" class="form-control" id="date" name="date"
                                            value="{{ old('date', $stockOpname->date ? $stockOpname->date->format('Y-m-d') : date('Y-m-d')) }}">
                                    </div>
                                </div>
                            </div>

                            {{-- Available Quantity --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="available_quantity" class="fw-semibold">Available Quantity:</label>
                                </div>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-box"></i></div>
                                        <input type="text" inputmode="numeric" class="form-control"
                                            id="available_quantity" name="available_quantity"
                                            value="{{ old('available_quantity', $stockOpname->available_quantity) }}"
                                            placeholder="Available Quantity">
                                    </div>
                                </div>
                            </div>

                            {{-- Status --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="status" class="fw-semibold">Status:</label>
                                </div>
                                <div class="col-lg-10">
                                    <select class="form-control" id="status" name="status">
                                        <option value="Gain" {{ $stockOpname->status == 'Gain' ? 'selected' : '' }}>Gain
                                        </option>
                                        <option value="Loss" {{ $stockOpname->status == 'Loss' ? 'selected' : '' }}>Loss
                                        </option>
                                    </select>
                                </div>
                            </div>

                            {{-- Notes --}}
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="notes" class="fw-semibold">Notes:</label>
                                </div>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-file-text"></i></div>
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
        const formatID = new Intl.NumberFormat('id-ID');

        function formatNumberInput(el) {
            let raw = el.value.replace(/\D/g, '');
            el.value = raw ? formatID.format(raw) : '';
        }

        function parseNumberValue(str) {
            return parseFloat(str.replace(/\./g, '').replace(',', '.')) || 0;
        }

        document.addEventListener('input', function(e) {
            if (e.target.matches('#available_quantity')) {
                formatNumberInput(e.target);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const qtyInput = document.getElementById('available_quantity');
            if (qtyInput.value.trim() !== '') {
                qtyInput.value = formatID.format(parseNumberValue(qtyInput.value));
            }

            const form = document.getElementById('stockOpnameForm');
            form.addEventListener('submit', function(e) {
                let isValid = true;
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const product = form.querySelector('#product');
                const date = form.querySelector('#date');
                const availableQty = form.querySelector('#available_quantity');

                const availableVal = parseNumberValue(availableQty.value);

                if (!product.value) {
                    isValid = false;
                    showError(product, 'Produk wajib dipilih');
                }

                if (!date.value.trim()) {
                    isValid = false;
                    showError(date, 'Tanggal wajib diisi');
                }

                if (availableVal <= 0) {
                    isValid = false;
                    showError(availableQty, 'Available quantity minimal 1');
                }

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                // bersihkan format ribuan sebelum submit
                availableQty.value = availableQty.value.replace(/\./g, '');
            });

            function showError(el, message) {
                if (!el) return;
                el.classList.add('is-invalid');
                const parent = el.closest('.input-group') || el.parentNode;
                const existing = parent.querySelector('.invalid-feedback');
                if (existing) existing.remove();

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                feedback.textContent = message;
                parent.appendChild(feedback);
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
