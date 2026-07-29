<div class="tab-pane fade {{ $accountsTabActive ? 'show active' : '' }}" id="customer-accounts-tab-pane" role="tabpanel"
    aria-labelledby="customer-accounts-tab" tabindex="0">
    <div class="row">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body p-0">
                    <div class="row g-3 p-2 justify-content-end align-items-end">
                        <div class="col-lg-3">
                            <label for="customerAccountNameFilter" class="fw-semibold fs-12">Account / Customer Name</label>
                            <input type="text" id="customerAccountNameFilter" class="form-control"
                                style="padding: 0.25rem 0.5rem; font-size: 0.875rem;"
                                autocomplete="off" placeholder="Search account or customer name...">
                        </div>
                        <div class="col-auto">
                            <a href="/erp/customer-accounts/create" class="btn btn-primary text-nowrap">
                                <i class="feather-plus me-2"></i>
                                <span>Create Customer Account</span>
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table bg-transparent table-hover" id="customerAccountList">
                            <thead>
                                <tr>
                                    <th class="wd-30">No</th>
                                    <th>Account - Customer</th>
                                    <th>Name</th>
                                    <th>WhatsApp Number</th>
                                    <th>Status</th>
                                    <th>Status Buat Baru/Reset Password</th>
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
    </style>
@endpush

@push('modals')
    @include('erp.pages.customer-accounts.partials.password-reset-modal')

    <div class="modal fade" id="modalDeleteCustomerAccount" tabindex="-1" aria-labelledby="deleteCustomerAccountModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteCustomerAccount">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteCustomerAccountModalLabel">Hapus Customer Account</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus customer account <strong id="customerAccountName"></strong>?</p>
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
    @include('erp.pages.customer-accounts.partials.password-reset-script')

    <script>
        $(document).ready(function() {
            const accountFilter = $('#customerAccountNameFilter');
            let lastAccountKeyword = accountFilter.val().trim();

            const customerAccountTable = $('#customerAccountList').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true,
                scrollY: 600,
                scroller: true,
                paging: true,
                searching: false,
                lengthChange: false,
                info: false,
                pagingType: 'simple',
                order: [[2, 'asc']],
                ajax: {
                    url: "{{ url('/erp/customer-accounts/data') }}",
                    data: function(d) {
                        d.name = accountFilter.val();
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'account_customer_name', name: 'account_customer_name', orderable: false, searchable: false },
                    { data: 'name', name: 'name' },
                    { data: 'whatsapp_number', name: 'whatsapp_number' },
                    { data: 'is_active', name: 'is_active' },
                    { data: 'password_reset_status', name: 'password_reset_status', orderable: false, searchable: false },
                    { data: 'action', name: 'action', orderable: false, searchable: false, visible: false }
                ]
            });

            accountFilter.on('keypress', function(e) {
                if (e.which !== 13) return;
                e.preventDefault();
                const keyword = $(this).val().trim();
                if (keyword !== lastAccountKeyword) {
                    $(this).val(keyword);
                    lastAccountKeyword = keyword;
                    customerAccountTable.ajax.reload(null, true);
                }
            });

            accountFilter.on('input', function() {
                if ($(this).val().trim() === '' && lastAccountKeyword !== '') {
                    lastAccountKeyword = '';
                    customerAccountTable.ajax.reload(null, true);
                }
            });

            $('#customerAccountList tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;
                const $tr = $(this);
                if ($tr.hasClass('action-row')) return;
                const row = customerAccountTable.row($tr);

                $('#customerAccountList tbody tr').removeClass('action-shown').next('.action-row').remove();
                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                    return;
                }

                const actionHtml = row.data().action;
                const colCount = $tr.find('td').length;
                $tr.after(`<tr class="action-row"><td colspan="${colCount}"><div class="d-flex justify-content-center">${actionHtml}</div></td></tr>`);
                $tr.addClass('action-shown');
            });

            $(document).on('click', function(e) {
                if ($(e.target).closest('#customerAccountList').length) return;
                $('#customerAccountList tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            document.querySelectorAll('#customerModuleTabs [data-bs-toggle="tab"]').forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function(event) {
                    const isAccounts = event.target.id === 'customer-accounts-tab';
                    const url = new URL(window.location.href);
                    if (isAccounts) {
                        url.searchParams.set('tab', 'accounts');
                        customerAccountTable.columns.adjust().draw(false);
                    } else {
                        url.searchParams.delete('tab');
                        if ($.fn.DataTable.isDataTable('#customerList')) {
                            $('#customerList').DataTable().columns.adjust().draw(false);
                        }
                    }
                    window.history.replaceState({}, '', url);
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteCustomerAccount');
            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                document.getElementById('formDeleteCustomerAccount').action = button.getAttribute('data-url');
                document.getElementById('customerAccountName').textContent = button.getAttribute('data-name');
            });
        });
    </script>
@endpush