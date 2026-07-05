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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/customers/update/{{ $customer->id }}" method="POST" id="customerForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row mb-2">
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
                            {{-- <div class="row mb-2">
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
                            </div> --}}
                            <div class="row mb-2 align-items-start">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Pilih Account Existing:</label>
                                </div>

                                <div class="col-lg-10">
                                    <select id="existing_account_picker" class="form-select existing-account-select"
                                        data-select2-selector="tag">
                                        <option value="">Pilih account existing</option>
                                        @foreach ($customerAccounts as $account)
                                            <option value="{{ $account->id }}" data-name="{{ $account->name ?? '-' }}"
                                                data-whatsapp="{{ $account->whatsapp_number ?? '-' }}">
                                                {{ $account->name ?? '-' }} - {{ $account->whatsapp_number }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <small class="text-muted">
                                        Pilih account yang punya akses ke outlet ini.
                                    </small>

                                    <div id="selectedAccountList" class="mt-2">
                                        @foreach ($customer->accounts as $account)
                                            <div class="selected-account-item border rounded p-1 mb-1">
                                                <div class="row align-items-center">
                                                    <div class="col-lg-5">
                                                        <div class="input-group">
                                                            <div class="input-group-text"><i class="feather-user"></i></div>
                                                            <input type="text" class="form-control selected-account-name"
                                                                name="existing_accounts[{{ $account->id }}][name]"
                                                                value="{{ $account->name ?? '-' }}" readonly>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-5">
                                                        <div class="input-group">
                                                            <div class="input-group-text"><i class="feather-phone"></i>
                                                            </div>
                                                            <input type="text"
                                                                class="form-control selected-account-whatsapp"
                                                                name="existing_accounts[{{ $account->id }}][whatsapp_number]"
                                                                value="{{ $account->whatsapp_number ?? '-' }}" readonly>
                                                        </div>
                                                    </div>

                                                    <div class="col-lg-2 d-flex gap-1">
                                                        <button type="button"
                                                            class="btn btn-warning btn-sm btn-edit-selected-account">
                                                            <i class="feather-edit"></i>
                                                        </button>

                                                        <button type="button"
                                                            class="btn btn-danger btn-sm btn-remove-selected-account">
                                                            <i class="feather-x"></i>
                                                        </button>
                                                    </div>

                                                    <input type="hidden" name="existing_account_ids[]"
                                                        value="{{ $account->id }}">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label class="fw-semibold">Tambah Account Baru:</label>
                                </div>

                                <div class="col-lg-10">
                                    <div id="accounts">
                                        <div class="account-item mb-1 row">
                                            <div class="col-lg-5">
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="feather-user"></i></div>
                                                    <input type="text" class="form-control" name="accounts[0][name]"
                                                        value="{{ old('accounts.0.name') }}" placeholder="Account Name">
                                                </div>
                                            </div>

                                            <div class="col-lg-5">
                                                <div class="input-group">
                                                    <div class="input-group-text"><i class="feather-phone"></i></div>
                                                    <input type="text" class="form-control phone-input"
                                                        name="accounts[0][whatsapp_number]"
                                                        value="{{ old('accounts.0.whatsapp_number') }}"
                                                        placeholder="Whatsapp Number">
                                                </div>
                                            </div>

                                            <div class="col-lg-2 d-flex">
                                                <button type="button" class="btn btn-danger btn-remove-account">
                                                    <i class="feather-x"></i> Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-success mt-1" id="add-account">
                                        <i class="feather-plus"></i> Add Account
                                    </button>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-lg-2">
                                    <label for="address" class="fw-semibold">Address(s):</label>
                                </div>
                                <div class="col-lg-10">
                                    <div id="addresses">
                                        @foreach ($customer->addresses as $index => $address)
                                            <div class="mb-2 row address-group">
                                                <div class="col-lg-3">
                                                    <div class="input-group">
                                                        <div class="input-group-text"><i class="feather-briefcase"></i>
                                                        </div>
                                                        <input type="text" class="form-control"
                                                            name="addresses[{{ $index }}][business_name]"
                                                            value="{{ old("addresses.$index.business_name", $address->business_name) }}"
                                                            placeholder="Branch Name">
                                                    </div>
                                                </div>
                                                <div class="col-lg-5 mb-0">
                                                    <div class="input-group">
                                                        <div class="input-group-text"><i class="feather-book"></i></div>
                                                        <textarea class="form-control" name="addresses[{{ $index }}][address]" placeholder="Address">{{ old("addresses.$index.address", $address->address) }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-lg-3 mb-0">
                                                    <div class="input-group">
                                                        <div class="input-group-text"><i class="feather-map-pin"></i>
                                                        </div>
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
                                    <button type="button" class="btn btn-success mt-1" id="add-address">
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

@push('modals')
    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteAddressModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">Hapus Alamat?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus alamat ini?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Yes, Delete</button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">Hapus Account?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus row account ini?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteAccountBtn" class="btn btn-danger">
                        Yes, Delete
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteSelectedAccountModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title text-white">Hapus Customer Account?</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus customer account ini dari outlet?
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDeleteSelectedAccountBtn" class="btn btn-danger">
                        Yes, Delete
                    </button>
                </div>

            </div>
        </div>
    </div>
@endpush

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
            wrapper.classList.add('mb-2', 'row', 'address-group');
            wrapper.innerHTML = `
                <div class="col-lg-3">
                    <div class="input-group">
                        <div class="input-group-text"><i class="feather-briefcase"></i></div>
                        <input type="text" class="form-control" name="addresses[${addressIndex}][business_name]" placeholder="Branch Name">
                    </div>
                </div>
                <div class="col-lg-5 mb-0">
                    <div class="input-group">
                        <div class="input-group-text"><i class="feather-book"></i></div>
                        <textarea class="form-control" name="addresses[${addressIndex}][address]" placeholder="Address"></textarea>
                    </div>
                </div>
                <div class="col-lg-3 mb-0">
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

        let addressToDelete = null;

        // EVENT REMOVE ADDRESS (BUKA MODAL)
        document.getElementById('addresses').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove');
            if (!btn) return;

            // Simpan elemen address-item yg mau dihapus
            addressToDelete = btn.closest('.address-group');

            // Tampilkan modal
            const modalEl = document.getElementById('deleteAddressModal');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        });

        // KONFIRMASI DELETE
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (addressToDelete) {
                addressToDelete.remove();
                updateRemoveButtons();
                addressToDelete = null;
            }

            const modalEl = document.getElementById('deleteAddressModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
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
                // {
                //     selector: 'input[name="phone"]',
                //     message: 'No Whatsapp wajib diisi'
                // },
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

            const selectedExistingCount = form.querySelectorAll('input[name="existing_account_ids[]"]').length;

            const filledNewAccounts = Array.from(form.querySelectorAll('.phone-input'))
                .filter(input => input.value.trim() !== '')
                .length;

            if (selectedExistingCount === 0 && filledNewAccounts === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Minimal pilih account existing atau input nomor baru.',
                });

                isValid = false;
            }

            const addressGroups = form.querySelectorAll('.address-group');
            addressGroups.forEach((group, index) => {
                const addressInput = group.querySelector(`textarea[name^="addresses"][name$="[address]"]`);
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

        // document.getElementById('phone').addEventListener('paste', function(e) {
        //     e.preventDefault();
        //     let text = e.clipboardData.getData('text');
        //     text = text.replace(/\D/g, '');
        //     this.value = text;
        // });

        // document.getElementById('phone').addEventListener('input', function() {
        //     this.value = this.value.replace(/\D/g, '');
        // });

        $(document).ready(function() {
            let accountIndex = 1;

            function updateRemoveAccountButtons() {
                document.querySelectorAll('#accounts .btn-remove-account').forEach(btn => {
                    btn.classList.remove('d-none');
                });
            }

            document.getElementById('add-account').addEventListener('click', function() {
                const wrapper = document.createElement('div');
                wrapper.className = 'account-item mb-1 row';

                wrapper.innerHTML = `
                    <div class="col-lg-5">
                        <div class="input-group">
                            <div class="input-group-text"><i class="feather-user"></i></div>
                                <input type="text"
                                    class="form-control"
                                    name="accounts[${accountIndex}][name]"
                                    placeholder="Account Name">
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="input-group">
                                <div class="input-group-text"><i class="feather-phone"></i></div>
                                    <input type="text"
                                        class="form-control phone-input"
                                        name="accounts[${accountIndex}][whatsapp_number]"
                                        placeholder="Whatsapp Number">
                                </div>
                            </div>

                        <div class="col-lg-2 d-flex">
                            <button type="button" class="btn btn-danger btn-remove-account">
                                <i class="feather-x"></i> Remove
                            </button>
                        </div>
                `;

                document.getElementById('accounts').appendChild(wrapper);
                accountIndex++;
                updateRemoveAccountButtons();
            });

            let accountToDelete = null;

            document.getElementById('accounts').addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-remove-account');
                if (!btn) return;

                accountToDelete = btn.closest('.account-item');

                const modalEl = document.getElementById('deleteAccountModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });

            document.getElementById('confirmDeleteAccountBtn').addEventListener('click', function() {
                if (accountToDelete) {
                    accountToDelete.remove();
                    updateRemoveAccountButtons();
                    accountToDelete = null;
                }

                const modalEl = document.getElementById('deleteAccountModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
            });

            document.getElementById('accounts').addEventListener('input', function(e) {
                if (e.target.classList.contains('phone-input')) {
                    e.target.value = e.target.value.replace(/\D/g, '');
                }
            });

            document.getElementById('accounts').addEventListener('paste', function(e) {
                if (!e.target.classList.contains('phone-input')) return;

                e.preventDefault();

                let text = e.clipboardData.getData('text');
                text = text.replace(/\D/g, '');
                e.target.value = text;
            });

            document.addEventListener('DOMContentLoaded', updateRemoveAccountButtons);
            $('#existing_account_picker').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih account existing',
                width: '100%',
                dropdownParent: $('#customerForm'),
                minimumResultsForSearch: 0,
                allowClear: true,
            });

            $('#existing_account_picker').on('change', function() {
                const accountId = $(this).val();
                const accountText = $('#existing_account_picker option:selected').text().trim();

                if (!accountId) return;

                const alreadySelected = document.querySelector(
                    `input[name="existing_account_ids[]"][value="${accountId}"]`
                );

                if (alreadySelected) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sudah dipilih',
                        text: 'Account ini sudah ada di list.',
                    });

                    $(this).val('').trigger('change');
                    return;
                }

                const selectedOption = $('#existing_account_picker option:selected');
                const accountName = selectedOption.data('name') ?? '-';
                const accountWhatsapp = selectedOption.data('whatsapp') ?? '-';

                const item = `
                    <div class="selected-account-item border rounded p-1 mb-1">
                        <div class="row align-items-center">
                            <div class="col-lg-5">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-user"></i></div>
                                    <input type="text"
                                    class="form-control selected-account-name"
                                        name="existing_accounts[${accountId}][name]"
                                        value="${accountName}"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-lg-5">
                                <div class="input-group">
                                    <div class="input-group-text"><i class="feather-phone"></i></div>
                                    <input type="text"
                                        class="form-control selected-account-whatsapp"
                                        name="existing_accounts[${accountId}][whatsapp_number]"
                                        value="${accountWhatsapp}"
                                        readonly>
                                </div>
                            </div>

                            <div class="col-lg-2 d-flex gap-1">
                                <button type="button" class="btn btn-warning btn-sm btn-edit-selected-account">
                                    <i class="feather-edit"></i>
                                </button>

                                <button type="button" class="btn btn-danger btn-sm btn-remove-selected-account">
                                    <i class="feather-x"></i>
                                </button>
                            </div>

                            <input type="hidden" name="existing_account_ids[]" value="${accountId}">
                        </div>
                    </div>
                `;

                $('#selectedAccountList').append(item);

                $(this).val('').trigger('change');
            });

            // $('#selectedAccountList').on('click', '.btn-remove-selected-account', function() {
            //     $(this).closest('.selected-account-item').remove();
            // });

            let selectedAccountToDelete = null;

            $('#selectedAccountList').on('click', '.btn-remove-selected-account', function() {
                selectedAccountToDelete = $(this).closest('.selected-account-item');

                const modalEl = document.getElementById('deleteSelectedAccountModal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });

            $('#confirmDeleteSelectedAccountBtn').on('click', function() {
                if (selectedAccountToDelete) {
                    selectedAccountToDelete.remove();
                    selectedAccountToDelete = null;
                }

                const modalEl = document.getElementById('deleteSelectedAccountModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
            });

            $('#selectedAccountList').on('click', '.btn-edit-selected-account', function() {
                const item = $(this).closest('.selected-account-item');

                const nameInput = item.find('.selected-account-name');
                const whatsappInput = item.find('.selected-account-whatsapp');

                const isReadOnly = nameInput.prop('readonly');

                nameInput.prop('readonly', !isReadOnly);
                whatsappInput.prop('readonly', !isReadOnly);

                if (isReadOnly) {
                    $(this)
                        .removeClass('btn-warning')
                        .addClass('btn-success')
                        .html('<i class="feather-check"></i>');

                    nameInput.focus();
                } else {
                    $(this)
                        .removeClass('btn-success')
                        .addClass('btn-warning')
                        .html('<i class="feather-edit"></i>');
                }
            });

            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });
        });
    </script>
@endpush
