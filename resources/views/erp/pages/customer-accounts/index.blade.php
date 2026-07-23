@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #customerAccountList td.desktop-only,
            #customerAccountList th.desktop-only {
                display: none !important;
            }
        }

        #customerAccountList {
            width: 100% !important;
            min-width: 0;
        }

        #customerAccountList_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
            height: calc(100vh - 260px) !important;
            min-height: calc(100vh - 260px) !important;
            max-height: calc(100vh - 260px) !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Customer Account</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Customer Account</li>
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
                    <a href="/erp/customer-accounts/create" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Account</span>
                    </a>
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
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
            });
        </script>
    @endif

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
                    <div class="card-body p-0">
                        <div class="row g-3 p-2 justify-content-end">
                            <div class="col-lg-3">
                                <label for="name" class="fw-semibold fs-12">Account Name</label>
                                <input type="text" id="name" class="form-control"
                                    style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                    placeholder="Search account name...">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table bg-transparent table-hover" id="customerAccountList">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Name</th>
                                        <th>WhatsApp Number</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalResetCustomerPassword" tabindex="-1"
        aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="formResetCustomerPassword">
                @csrf

                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="resetPasswordModalLabel">Reset Password Customer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div id="resetPasswordConfirmation">
                            <p>
                                Buat link reset password untuk
                                <strong id="resetPasswordCustomerName"></strong>?
                            </p>
                            <p class="text-muted mb-0">
                                Link hanya berlaku selama 1 jam. Pembuatan link baru akan membatalkan link sebelumnya.
                            </p>
                        </div>

                        <div id="resetPasswordResult" class="d-none">
                            <label for="resetPasswordUrl" class="form-label fw-semibold">Link Website</label>
                            <div class="input-group">
                                <input type="text" id="resetPasswordUrl" class="form-control" readonly>
                                <button type="button" class="btn btn-outline-primary" id="copyResetPasswordUrl">
                                    <i class="feather feather-copy me-1"></i> Salin
                                </button>
                            </div>
                            <p class="text-success mt-3 mb-0" id="resetPasswordMessage"></p>
                        </div>

                        <div class="alert alert-danger d-none mt-3 mb-0" id="resetPasswordError"></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Tutup</button>
                        <button type="submit" class="btn btn-primary btn-md" id="generateResetPasswordLink">
                            Generate Link
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalDeleteCustomerAccount" tabindex="-1" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteCustomerAccount">
                @csrf
                @method('PUT')

                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Customer Account</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus customer account <strong id="customerAccountName"></strong>?
                        </p>
                        <p class="text-muted">Data yang dihapus tidak dapat dikembalikan.</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#customerAccountList')) {
                $('#customerAccountList').DataTable().clear().destroy();
            }

            const dataTable = $('#customerAccountList').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                scrollY: 600,
                scroller: true,
                paging: true,
                searching: false,
                lengthChange: false,
                info: false,
                pagingType: "simple",
                ajax: {
                    url: "{{ url('/erp/customer-accounts/data') }}",
                    data: function(d) {
                        d.name = $('#name').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'name',
                        name: 'name',
                    },
                    {
                        data: 'whatsapp_number',
                        name: 'whatsapp_number',
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        visible: false,
                    },
                ]
            });

            let lastKeyword = '';

            $('#name').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();

                    const keyword = $(this).val().trim();

                    if (keyword !== lastKeyword) {
                        lastKeyword = keyword;
                        dataTable.ajax.reload();
                    }
                }
            });

            $('#name').on('input', function() {
                const val = $(this).val().trim();

                if (val === '' && lastKeyword !== '') {
                    lastKeyword = '';
                    dataTable.ajax.reload();
                }
            });

            $('#customerAccountList tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                if ($tr.hasClass('action-row')) return;

                let row = dataTable.row($tr);

                $('#customerAccountList tbody tr')
                    .removeClass('action-shown')
                    .next('.action-row')
                    .remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let actionHtml = row.data().action;

                    let colCount = $tr.find('td').length;
                    let $actionRow = $(`
                        <tr class="action-row">
                            <td colspan="${colCount}">
                                <div class="d-flex justify-content-center">
                                    ${actionHtml}
                                </div>
                            </td>
                        </tr>
                    `);

                    $tr.after($actionRow);
                    $tr.addClass('action-shown');
                }
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('#customerAccountList').length) return;

                $('#customerAccountList tbody tr')
                    .removeClass('action-shown')
                    .next('.action-row')
                    .remove();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const resetModal = document.getElementById('modalResetCustomerPassword');
            const resetForm = document.getElementById('formResetCustomerPassword');
            const resetNameHolder = document.getElementById('resetPasswordCustomerName');
            const resetConfirmation = document.getElementById('resetPasswordConfirmation');
            const resetResult = document.getElementById('resetPasswordResult');
            const resetUrl = document.getElementById('resetPasswordUrl');
            const resetMessage = document.getElementById('resetPasswordMessage');
            const resetError = document.getElementById('resetPasswordError');
            const generateButton = document.getElementById('generateResetPasswordLink');
            const copyButton = document.getElementById('copyResetPasswordUrl');
            let resetEndpoint = '';

            resetModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                resetEndpoint = button.getAttribute('data-url');
                resetNameHolder.textContent = button.getAttribute('data-name');
                resetConfirmation.classList.remove('d-none');
                resetResult.classList.add('d-none');
                resetError.classList.add('d-none');
                resetError.textContent = '';
                resetUrl.value = '';
                generateButton.classList.remove('d-none');
                generateButton.disabled = false;
                generateButton.textContent = 'Generate Link';
            });

            resetForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                generateButton.disabled = true;
                generateButton.textContent = 'Memproses...';
                resetError.classList.add('d-none');

                try {
                    const response = await fetch(resetEndpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': resetForm.querySelector('input[name="_token"]').value,
                        },
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Gagal membuat link reset password.');
                    }

                    resetUrl.value = payload.data.reset_url;
                    resetMessage.textContent = payload.message;
                    resetConfirmation.classList.add('d-none');
                    resetResult.classList.remove('d-none');
                    generateButton.classList.add('d-none');
                } catch (error) {
                    resetError.textContent = error.message || 'Gagal membuat link reset password.';
                    resetError.classList.remove('d-none');
                    generateButton.disabled = false;
                    generateButton.textContent = 'Generate Link';
                }
            });

            copyButton.addEventListener('click', async function() {
                try {
                    await navigator.clipboard.writeText(resetUrl.value);
                } catch (error) {
                    resetUrl.select();
                    document.execCommand('copy');
                }

                copyButton.innerHTML = '<i class="feather feather-check me-1"></i> Tersalin';
                window.setTimeout(function() {
                    copyButton.innerHTML = '<i class="feather feather-copy me-1"></i> Salin';
                }, 1500);
            });

            const modal = document.getElementById('modalDeleteCustomerAccount');
            const form = document.getElementById('formDeleteCustomerAccount');
            const nameHolder = document.getElementById('customerAccountName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });
    </script>
@endpush
