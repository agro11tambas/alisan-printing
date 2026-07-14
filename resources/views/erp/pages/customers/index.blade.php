@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #customerList td.desktop-only,
            #customerList th.desktop-only {
                display: none !important;
            }
        }

        #customerList {
            width: 100% !important;
            min-width: 0;
        }

        #customerList_wrapper .dataTables_scrollBody {
            background-image: none !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }
    </style>
@endpush

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
                    <a href="/erp/customers/create-customer" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Customer</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-3 p-2 justify-content-between">
                            <div class="col-lg-4 me-2">

                            </div>
                            <div class="col-lg-4">
                                <div class="row g-3 justify-content-end">
                                    <div class="col-lg-6">
                                        <label for="name" class="fw-semibold fs-12">Customer Name</label>
                                        <input type="text" id="name" name="name" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                            placeholder="Search Customer Name...">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table bg-transparent table-hover" id="customerList">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Nama</th>
                                        <th>Phone</th>
                                        <th>Customer Deposit</th>
                                        <th>User</th>
                                        <!-- <th class="text-end">Actions</th> -->
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalDeleteCustomer" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteCustomer">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Customer</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus customer <strong id="customerName"></strong>?</p>
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

    <div class="modal fade-scale" id="modalCustomerDeposit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header" id="depositModalHeader">
                    <h2 class="d-flex flex-column mb-0">
                        <span class="fs-18 fw-bold mb-1" id="depositModalTitle"></span>
                        <span class="fs-12 text-muted fw-normal" id="depositCustomerName"></span>
                    </h2>
                    <a href="javascript:void(0)" class="avatar-text avatar-md bg-soft-danger close-icon"
                        data-bs-dismiss="modal">
                        <i class="feather-x text-danger"></i>
                    </a>
                </div>
                <form method="POST" id="formCustomerDeposit">
                    @csrf
                    <input type="hidden" name="customer_id" id="depositCustomerId">
                    <input type="hidden" name="deposit_type" id="depositType">
                    <div class="modal-body">
                        <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <label class="fw-semibold">Current Deposit:</label>
                                <h5 class="fw-bold text-primary" id="depositCurrentAmount">Rp. 0</h5>
                            </div>
                        </div>
                        <div class="row g-3 mb-2">
                            <div class="col-md-12">
                                <label for="deposit_amount" class="fw-semibold">Amount:</label>
                                <input type="text" class="form-control" id="deposit_amount" name="deposit_amount"
                                    value="0" required>
                                <small class="text-danger d-none" id="error_deposit_amount"></small>
                            </div>
                        </div>
                        {{-- <div class="row g-3">
                            <div class="col-md-12">
                                <label for="deposit_note" class="fw-semibold">Note (optional):</label>
                                <textarea class="form-control" id="deposit_note" name="deposit_note" rows="2"
                                    placeholder="e.g. DP order #123"></textarea>
                            </div>
                        </div> --}}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn" id="depositSubmitBtn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush


@push('scripts')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#customerList')) {
                $('#customerList').DataTable().clear().destroy();
            }

            const dataTable = $('#customerList').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                scrollY: 'calc(100vh - 260px)',
                scroller: true,
                paging: true,
                searching: false,
                lengthChange: false,
                info: false,
                pagingType: "simple",
                stateSave: true,
                stateDuration: -1,

                stateSaveParams: function(settings, data) {
                    data.customer_name_filter = $('#name').val();
                },

                stateLoadParams: function(settings, data) {
                    if (data.customer_name_filter) {
                        $('#name').val(data.customer_name_filter);
                        lastKeyword = data.customer_name_filter;
                    }
                },

                ajax: {
                    url: "{{ url('/erp/customers/data') }}",
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
                        data: 'phone',
                        name: 'phone',
                    },
                    {
                        data: 'customer_deposit',
                        name: 'customer_deposit',
                    },
                    {
                        data: 'user',
                        name: 'user',
                    },
                    // {
                    //     data: 'action',
                    //     name: 'action',
                    //     orderable: false,
                    //     searchable: false,
                    //     visible: false,
                    // }
                ]
            });

            // $('#name').on('keyup', function() {
            //     dataTable.ajax.reload();
            // });

            let lastKeyword = '';

            $('#name').on('keypress', function(e) {
                if (e.which === 13) { // ENTER
                    e.preventDefault();

                    const keyword = $(this).val().trim();
                    if (keyword !== lastKeyword) {
                        lastKeyword = keyword;
                        dataTable.ajax.reload(null, true); // Force reset paging for scroller
                    }
                }
            });

            // Handle when user clears input and presses ENTER, or clicks a clear button.
            // If they want to search, they must press ENTER. 
            // BUT if they clear the input, maybe they want it to auto-reload?
            // "aku hapus andi nya tabelnya malah kosong" - we must ensure it doesn't empty out!
            $('#name').on('input', function() {
                const val = $(this).val().trim();
                if (val === '' && lastKeyword !== '') {
                    // Allow auto-reload ONLY when cleared completely to revert to original state
                    lastKeyword = '';
                    dataTable.state.clear();
                    dataTable.ajax.reload(null, true); // Force reset paging for scroller
                }
            });

            $('#customerList tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#customerList tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#customerList').length) return;

                $('#customerList tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#customerList tbody tr, #customerListMobile tbody tr').length) {
                    $('#customerList tbody tr.shown, #customerListMobile tbody tr.shown').each(function() {
                        var tr = $(this);
                        var table = tr.closest('table').attr('id') === 'customerList' ? dataTable :
                            dataTableMobile;
                        var row = table.row(tr);
                        if (row.child.isShown()) {
                            row.child.hide();
                            tr.removeClass('shown');
                        }
                    });
                }
            });

            const depositModal = document.getElementById('modalCustomerDeposit');
            const depositForm = document.getElementById('formCustomerDeposit');

            function formatRupiah(el) {
                let raw = el.value.replace(/\D/g, '');
                el.value = raw ? parseInt(raw).toLocaleString('id-ID') : '';
            }

            document.getElementById('deposit_amount').addEventListener('input', function() {
                formatRupiah(this);
            });

            depositModal.addEventListener('show.bs.modal', function(event) {
                const btn = event.relatedTarget;
                const type = btn.getAttribute('data-type'); // 'add' | 'subtract'

                document.getElementById('depositCustomerId').value = btn.getAttribute('data-id');
                document.getElementById('depositCustomerName').textContent = btn.getAttribute('data-name');
                document.getElementById('depositType').value = type;

                const deposit = parseFloat(btn.getAttribute('data-deposit')) || 0;
                document.getElementById('depositCurrentAmount').textContent =
                    'Rp. ' + deposit.toLocaleString('id-ID');

                // Ubah title & warna header sesuai type
                const isAdd = type === 'add';
                document.getElementById('depositModalTitle').textContent = isAdd ? 'Add Customer Deposit' :
                    'Subtract Customer Deposit';
                document.getElementById('depositModalHeader').className = 'modal-header ' + (isAdd ?
                    'bg-soft-success' :
                    'bg-soft-danger');
                const submitBtn = document.getElementById('depositSubmitBtn');
                submitBtn.className = 'btn ' + (isAdd ? 'btn-success' : 'btn-danger');
                submitBtn.textContent = isAdd ? 'Add' : 'Subtract';

                // Reset field
                document.getElementById('deposit_amount').value = '0';
                document.getElementById('deposit_note').value = '';
                document.getElementById('error_deposit_amount').classList.add('d-none');
            });

            depositForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const customerId = document.getElementById('depositCustomerId').value;
                // const formData = new FormData(depositForm);

                const rawAmount = document.getElementById('deposit_amount').value.replace(/\./g, '');
                const formData = new FormData(depositForm);
                formData.set('deposit_amount', rawAmount);

                fetch(`/erp/customers/${customerId}/deposit`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData,
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            bootstrap.Modal.getInstance(depositModal).hide();
                            dataTable.ajax.reload(null, false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message
                            });
                        } else {
                            if (res.errors?.deposit_amount) {
                                const err = document.getElementById('error_deposit_amount');
                                err.textContent = res.errors.deposit_amount[0];
                                err.classList.remove('d-none');
                            }
                        }
                    })
                    .catch(() => Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan.'
                    }));
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteCustomer');
            const form = document.getElementById('formDeleteCustomer');
            const nameHolder = document.getElementById('customerName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });
    </script>
@endpush
