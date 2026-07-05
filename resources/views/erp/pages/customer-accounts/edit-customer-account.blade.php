@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Customer Accounts</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/customer-accounts">Customer Accounts</a></li>
                <li class="breadcrumb-item">Edit</li>
            </ul>
        </div>

        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/customer-accounts" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="customerAccountForm">
                        <i class="feather-save me-2"></i>
                        <span>Save Changes</span>
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

    <div class="main-content m-0 m-md-2 m-lg-2 p-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/customer-accounts/update/{{ $customerAccount->id }}" method="POST"
                        id="customerAccountForm">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-user"></i></div>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $customerAccount->name) }}"
                                            placeholder="Account Name">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="whatsapp_number" class="fw-semibold">WhatsApp Number:</label>
                                </div>
                                <div class="col-lg-10">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-phone"></i></div>
                                        <input type="text"
                                            class="form-control phone-input @error('whatsapp_number') is-invalid @enderror"
                                            id="whatsapp_number" name="whatsapp_number"
                                            value="{{ old('whatsapp_number', $customerAccount->whatsapp_number) }}"
                                            placeholder="Whatsapp Number">
                                        @error('whatsapp_number')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="is_active" class="fw-semibold">Status:</label>
                                </div>
                                <div class="col-lg-10">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_active" value="0">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                            value="1"
                                            {{ old('is_active', $customerAccount->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Last Login:</label>
                                </div>
                                <div class="col-lg-10">
                                    <span class="text-muted">
                                        {{ $customerAccount->last_login_at ? $customerAccount->last_login_at->format('d M Y H:i') : '-' }}
                                    </span>
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
        document.getElementById('whatsapp_number').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        document.getElementById('whatsapp_number').addEventListener('paste', function(e) {
            e.preventDefault();

            let text = e.clipboardData.getData('text');
            this.value = text.replace(/\D/g, '');
        });

        document.getElementById('customerAccountForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            let isValid = true;

            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback.client-error').forEach(el => el.remove());

            const name = document.getElementById('name');
            const whatsapp = document.getElementById('whatsapp_number');

            if (name.value.trim() === '') {
                showError(name, 'Nama account wajib diisi');
                isValid = false;
            }

            if (whatsapp.value.trim() === '') {
                showError(whatsapp, 'Nomor WhatsApp wajib diisi');
                isValid = false;
            }

            if (isValid) form.submit();
        });

        function showError(input, message) {
            input.classList.add('is-invalid');

            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback client-error';
            feedback.textContent = message;

            input.closest('.input-group').appendChild(feedback);
        }
    </script>
@endpush
