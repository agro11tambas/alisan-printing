@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Product Unit</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/products/units">Product Unit</a></li>
                <li class="breadcrumb-item">Edit Product Unit</li>
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
                    <a href="/erp/products/units" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>

                    <button type="submit" class="btn btn-primary" form="productUnitForm">
                        <i class="feather-save me-2"></i>
                        <span>Update Product Unit</span>
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

    @if ($errors->any())
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ $errors->first() }}",
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/products/units/update/{{ $unit->id }}" method="POST" id="productUnitForm">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Unit Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <i class="feather-box"></i>
                                        </div>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name', $unit->name) }}"
                                            placeholder="Contoh: Pcs, Dus, Pack, Kg, Meter">
                                    </div>

                                    @error('name')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="description" class="fw-semibold">Description:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text">
                                            <i class="feather-type"></i>
                                        </div>
                                        <textarea name="description" id="description" cols="30" rows="5" placeholder="Deskripsi unit, boleh kosong"
                                            class="form-control">{{ old('description', $unit->description) }}</textarea>
                                    </div>

                                    @error('description')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>

                            <div class="alert alert-soft-primary mt-4 mb-0">
                                <div class="fw-semibold mb-1">Catatan</div>
                                <div>
                                    Di halaman ini hanya edit master satuan seperti
                                    <strong>Pcs</strong>, <strong>Dus</strong>, <strong>Pack</strong>,
                                    <strong>Kg</strong>, atau <strong>Meter</strong>.
                                </div>
                                <div>
                                    Konversi dan harga per unit tetap diatur saat create/edit product.
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
            const form = document.getElementById('productUnitForm');

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const nameField = form.querySelector('input[name="name"]');

                let isValid = true;

                if (!nameField.value.trim()) {
                    showError(nameField, 'Unit Name wajib diisi');
                    isValid = false;
                }

                if (!isValid) return;

                form.submit();
            });

            function showError(input, message) {
                if (!input) return;

                input.classList.add('is-invalid');

                const parent = input.closest('.input-group') || input.closest('div');

                if (!parent) return;

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;

                parent.appendChild(feedback);
            }
        });
    </script>
@endpush
