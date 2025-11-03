@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Customers</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
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
                        <span>Edit Customer</span>
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
                    <form action="/erp/customers/update/{{ $customer->id }}" method="POST" id="customerForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="name" class="fw-semibold">Name:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-user"></i></div>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old('name', $customer->name) }}" placeholder="Name">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="phone" class="fw-semibold">Phone:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <div class="input-group">
                                        <div class="input-group-text"><i class="feather-phone"></i></div>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                            value="{{ old('phone', $customer->phone) }}" placeholder="Phone">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-lg-2">
                                    <label for="address" class="fw-semibold">Address(s):</label>
                                </div>
                                <div class="col-lg-10">
                                    <div id="addresses">
                                        @foreach ($customer->addresses as $index => $address)
                                            <div class="mb-3 row address-group">
                                                <div class="col-lg-3">
                                                    <div class="input-group">
                                                        <div class="input-group-text"><i class="feather-briefcase"></i>
                                                        </div>
                                                        <input type="text" class="form-control"
                                                            name="addresses[{{ $index }}][business_name]"
                                                            value="{{ old("addresses.$index.business_name", $address->business_name) }}"
                                                            placeholder="Business Name">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 mb-0">
                                                    <div class="input-group">
                                                        <div class="input-group-text"><i class="feather-book"></i></div>
                                                        <input type="text" class="form-control"
                                                            name="addresses[{{ $index }}][address]"
                                                            value="{{ old("addresses.$index.address", $address->address) }}"
                                                            placeholder="Address">
                                                    </div>
                                                </div>
                                                <div class="col-lg-4 mb-0">
                                                    <div class="input-group">
                                                        <div class="input-group-text"><i class="feather-map-pin"></i></div>
                                                        <input type="text" class="form-control"
                                                            name="addresses[{{ $index }}][google_maps]"
                                                            value="{{ old("addresses.$index.google_maps", $address->google_maps) }}"
                                                            placeholder="Google Map">
                                                    </div>
                                                </div>
                                                <div class="col-lg-1">
                                                    <button type="button"
                                                        class="btn btn-danger btn-remove {{ $loop->first ? 'd-none' : '' }}">
                                                        <i class="feather-x"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-success mt-2" id="add-address">
                                        <i class="feather-plus"></i> Add Address
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
        let addressIndex = {{ $customer->addresses->count() }};

        function updateRemoveButtons() {
            const groups = document.querySelectorAll('#addresses .address-group');
            groups.forEach((group, idx) => {
                const btn = group.querySelector('.btn-remove');
                if (idx === 0) {
                    btn.classList.add('d-none');
                } else {
                    btn.classList.remove('d-none');
                }
            });
        }

        document.getElementById('add-address').addEventListener('click', function() {
            const wrapper = document.createElement('div');
            wrapper.classList.add('mb-3', 'row', 'address-group');
            wrapper.innerHTML = `
            <div class="col-lg-3">
                <div class="input-group">
                    <div class="input-group-text"><i class="feather-briefcase"></i></div>
                    <input type="text" class="form-control" name="addresses[${addressIndex}][business_name]" placeholder="Business Name">
                </div>
            </div>
            <div class="col-lg-4 mb-0">
                <div class="input-group">
                    <div class="input-group-text"><i class="feather-book"></i></div>
                    <input type="text" class="form-control" name="addresses[${addressIndex}][address]" placeholder="Address">
                </div>
            </div>
            <div class="col-lg-4 mb-0">
                <div class="input-group">
                    <div class="input-group-text"><i class="feather-map-pin"></i></div>
                    <input type="text" class="form-control" name="addresses[${addressIndex}][google_maps]" placeholder="Google Map">
                </div>
            </div>
            <div class="col-lg-1">
                <button type="button" class="btn btn-danger btn-remove"><i class="feather-x"></i></button>
            </div>
        `;
            document.getElementById('addresses').appendChild(wrapper);
            addressIndex++;
            updateRemoveButtons();
        });

        document.getElementById('addresses').addEventListener('click', function(e) {
            if (e.target.closest('.btn-remove')) {
                e.target.closest('.address-group').remove();
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
