@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Edit Invoice</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/invoices">Invoice</a></li>
                <li class="breadcrumb-item">Edit</li>
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
                    <a href="/erp/invoices" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="invoiceForm">
                        <i class="feather-edit me-2"></i>
                        <span>Edit Invoice</span>
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
                    <form action="/erp/invoices/update/{{ $invoice->id }}" method="POST" id="invoiceForm"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="logo" class="fw-semibold">Logo:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="file" class="form-control" id="logo" name="logo"
                                        accept="image/*">

                                    <div class="mt-1 d-flex align-items-center gap-3">
                                        @if ($invoice->logo)
                                            <div>
                                                <p class="text-muted mb-1 small">Current Logo:</p>
                                                <img src="{{ asset($invoice->logo) }}" alt="Current Logo"
                                                    class="img-thumbnail" style="max-height: 100px;">
                                            </div>
                                        @endif
                                        <div>
                                            <p class="text-muted mb-1 small">Preview New Logo:</p>
                                            <img id="logo-preview" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="New Logo Preview"
                                                class="img-thumbnail d-none" style="max-height: 100px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-user"></i></div>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name', $invoice->name) }}" placeholder="Name">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="bank_name" class="fw-semibold">Bank Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-layers"></i></div>
                                        <input type="text" class="form-control" id="bank_name" name="bank_name"
                                            value="{{ old('bank_name', $invoice->bank_name) }}" placeholder="Bank Name">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="account_number" class="fw-semibold">No Rek:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-link"></i></div>
                                        <input type="text" class="form-control" id="account_number" name="account_number"
                                            value="{{ old('account_number', $invoice->account_number) }}"
                                            placeholder="Account Number">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="address" class="fw-semibold">Address:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-map-pin"></i></div>
                                        <input type="text" class="form-control" id="address" name="address"
                                            value="{{ old('address', $invoice->address) }}" placeholder="Address">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Terms & Conditions</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div id="contents-wrapper">
                                        @if ($invoice->termAndConditions && $invoice->termAndConditions->count())
                                            @foreach ($invoice->termAndConditions as $index => $content)
                                                <div class="content-item mb-1 d-flex align-items-start">
                                                    <textarea class="form-control" name="contents[]" rows="1" placeholder="Enter content">{{ old("contents.$index", $content->content) }}</textarea>
                                                    <button type="button"
                                                        class="btn btn-danger ms-2 remove-content {{ $loop->first ? 'd-none' : '' }}">
                                                        Remove
                                                    </button>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="content-item mb-1 d-flex align-items-start">
                                                <textarea class="form-control" name="contents[]" rows="1" placeholder="Enter content"></textarea>
                                                <button type="button"
                                                    class="btn btn-danger ms-2 remove-content d-none">Remove</button>
                                            </div>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn-success mt-1" id="add-content-btn">+ Add
                                        Content</button>
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
            const form = document.getElementById('invoiceForm');
            const wrapper = document.getElementById('contents-wrapper');
            const addBtn = document.getElementById('add-content-btn');

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                const rules = [{
                        selector: 'input[name="name"]',
                        message: 'Nama wajib diisi'
                    },
                    {
                        selector: 'input[name="bank_name"]',
                        message: 'Nama Bank wajib diisi'
                    },
                    {
                        selector: 'input[name="account_number"]',
                        message: 'No Rekening wajib diisi'
                    },
                ];

                let isValid = true;
                rules.forEach(rule => {
                    const el = form.querySelector(rule.selector);
                    const val = el?.value ?? '';
                    if (val.trim() === '') {
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
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                parent.appendChild(feedback);
            }

            addBtn.addEventListener('click', function() {
                const newItem = document.createElement('div');
                newItem.className = 'content-item mb-1 d-flex align-items-start';
                newItem.innerHTML = `
                <textarea class="form-control" name="contents[]" rows="1" placeholder="Enter content"></textarea>
                <button type="button" class="btn btn-danger ms-2 remove-content">Remove</button>
            `;
                wrapper.appendChild(newItem);
                updateRemoveButtons();
            });

            wrapper.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-content')) {
                    e.target.parentElement.remove();
                    updateRemoveButtons();
                }
            });

            function updateRemoveButtons() {
                const allItems = wrapper.querySelectorAll('.content-item');
                allItems.forEach((item, index) => {
                    const removeBtn = item.querySelector('.remove-content');
                    if (index === 0) {
                        removeBtn.classList.add('d-none');
                    } else {
                        removeBtn.classList.remove('d-none');
                    }
                });
            }

            updateRemoveButtons();
        });

        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('logo-preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    preview.src = evt.target.result;
                    preview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = '#';
                preview.classList.add('d-none');
            }
        });
    </script>
@endpush
