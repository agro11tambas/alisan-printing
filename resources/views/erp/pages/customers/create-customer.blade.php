@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Customers</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item">Customers</li>
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
                    <a href="/erp/customers" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="customerForm">
                        <i class="feather-plus me-2"></i>
                        <span>Create Customer</span>
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
                    <form action="/erp/customers/store" method="POST" id="customerForm">
                        @csrf
                        @method('POST')
                        <div class="card-body">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-user"></i></div>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name') }}" placeholder="Name">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="phone" class="fw-semibold">Phone:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-phone"></i></div>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                            value="{{ old('phone') }}" placeholder="Phone"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="address" class="fw-semibold">Address(s):</label>
                                </div>
                                <div class="col-lg-10">
                                    <div id="addresses">
                                        <div class="address-item mb-2 row">
                                            <div class="col-lg-3">
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="feather-briefcase"></i></div>
                                                    <input type="text" class="form-control"
                                                        name="addresses[0][business_name]"
                                                        value="{{ old('addresses.0.business_name') }}"
                                                        placeholder="Business Name">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="feather-book"></i></div>
                                                    <input type="text" class="form-control" name="addresses[0][address]"
                                                        value="{{ old('addresses.0.address') }}" placeholder="Address">
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="feather-map-pin"></i></div>
                                                    <input type="text" class="form-control"
                                                        name="addresses[0][google_maps]"
                                                        value="{{ old('addresses.0.google_maps') }}"
                                                        placeholder="Google Map">
                                                </div>
                                            </div>
                                            <div class="col-lg-1 d-flex">
                                                <button type="button" class="btn btn-danger btn-remove d-none"><i
                                                        class="feather-x"></i> Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-success mt-2" id="add-address"><i
                                            class="feather-plus"></i> Add Address</button>
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
        let addressIndex = 1;

        function updateRemoveButtons() {
            const allItems = document.querySelectorAll('#addresses .address-item');
            allItems.forEach((item, idx) => {
                const removeBtn = item.querySelector('.btn-remove');
                if (idx === 0) {
                    removeBtn.classList.add('d-none');
                } else {
                    removeBtn.classList.remove('d-none');
                }
            });
        }

        document.getElementById('add-address').addEventListener('click', function() {
            const wrapper = document.createElement('div');
            wrapper.className = 'address-item mb-2 row';
            wrapper.innerHTML = `
            <div class="col-lg-3">
                <div class="input-group">
                    <div class="input-group-text"><i class="feather-briefcase"></i></div>
                    <input type="text" class="form-control" name="addresses[${addressIndex}][business_name]" placeholder="Business Name}">
                </div>
            </div>
            <div class="col-lg-4">
                <div class="input-group">
                    <div class="input-group-text"><i class="feather-book"></i></div>
                    <input type="text" class="form-control" name="addresses[${addressIndex}][address]" placeholder="Address}">
                </div>
            </div>
            <div class="col-lg-4">
                <div class="input-group">
                    <div class="input-group-text"><i class="feather-map-pin"></i></div>
                    <input type="text" class="form-control" name="addresses[${addressIndex}][google_maps]" placeholder="Google Map}">
                </div>
            </div>
            <div class="col-lg-1 d-flex">
                <button type="button" class="btn btn-danger btn-remove"><i class="feather-x"></i> Remove</button>
            </div>
        `;
            document.getElementById('addresses').appendChild(wrapper);
            addressIndex++;
            updateRemoveButtons();
        });

        document.getElementById('addresses').addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove') || e.target.closest('.btn-remove')) {
                e.target.closest('.address-item').remove();
                updateRemoveButtons();
            }
        });

        document.addEventListener('DOMContentLoaded', updateRemoveButtons);

        document.getElementById('customerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            const rules = [{
                    selector: 'input[name="name"]',
                    message: 'Nama Customer wajib diisi'
                },
                {
                    selector: 'input[name="phone"]',
                    message: 'No Whatsapp wajib diisi'
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

            const addressGroups = form.querySelectorAll('.address-group');
            addressGroups.forEach((group, index) => {
                const addressInput = group.querySelector(`input[name^="addresses"][name$="[address]"]`);
                const mapsInput = group.querySelector(`input[name^="addresses"][name$="[google_maps]"]`);

                if (!addressInput || addressInput.value.trim() === '') {
                    showError(addressInput, `Alamat ke-${index + 1} wajib diisi`);
                    isValid = false;
                }

                if (!mapsInput || mapsInput.value.trim() === '') {
                    showError(mapsInput, `Link Google Maps ke-${index + 1} wajib diisi`);
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
