@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #accountList td.desktop-only,
            #accountList th.desktop-only {
                display: none !important;
            }
        }

        #accountList {
            width: 100% !important;
            min-width: 0;
        }

        #accountList_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #accountList tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Account</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Account</li>
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
                    <a href="/erp/accounts/create-account" class="btn btn-primary">
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body p-0">
                    <div class="row g-3 p-4 justify-content-between">
                        <div class="col-lg-4 me-2">

                        </div>
                        <div class="col-lg-4">
                            <div class="row g-3 justify-content-end">
                                <div class="col-lg-6">
                                    <label for="name" class="fw-semibold fs-12">Bank Name</label>
                                    <input type="text" id="name" name="name" class="form-control"
                                        style="padding: 0.5rem 1rem; font-size: 0.875rem;"
                                        placeholder="Search Customer Name...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table bg-transparent table-hover table-vcenter text-nowrap mb-0" id="accountList">
                        <thead>
                            <tr>
                                {{-- <th class="wd-30">No</th> --}}
                                <th>Account Name</th>
                                <th>Account Type</th>
                                <!-- <th class="text-end">Actions</th> -->
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalDeleteAccount" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteAccount">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Account</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Account <strong id="accountName"></strong>?</p>
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
    <div class="modal fade" id="modalMarkDefault" tabindex="-1" aria-labelledby="markDefaultModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formMarkDefault">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white" id="markDefaultModalLabel">Jadikan Default Account</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menjadikan Account <strong id="markDefaultName"></strong> sebagai <span
                                class="fw-bold">default</span>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-md">Ya, Jadikan Default</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="modalRemoveDefault" tabindex="-1" aria-labelledby="removeDefaultModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formRemoveDefault">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="removeDefaultModalLabel">Hapus Default Account</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus status default dari Account <strong
                                id="removeDefaultName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Ya, Hapus Default</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {

            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;

            const dataTable = $('#accountList').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                order: [
                    [0, 'asc']
                ],
                data: [],
                columns: [
                    // {
                    //     data: 'DT_RowIndex',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'name'
                    },
                    {
                        data: 'type'
                    },
                ]
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/accounts/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 20,
                        length: 20,
                        name: $('#name').val(),
                        type: $('#type').val(),
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            dataTable.clear();
                            dataTable.rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                        }
                        isLoading = false;
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON?.message || 'Error loading data.');
                        isLoading = false;
                    }
                });
            }

            loadMoreData();

            let scrollTimeout = null;
            $('.dataTables_scrollBody').on('scroll', function() {
                clearTimeout(scrollTimeout);
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                scrollTimeout = setTimeout(() => {
                    if (scrollTop + clientHeight >= scrollHeight * 0.85) {
                        loadMoreData();
                    }
                }, 200);
            });

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                dataTable.clear().draw();
                loadMoreData();
            }

            $('#name, #type').on('change keyup', function() {
                resetAndReload();
            });

            $('#accountList tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#accountList tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#accountList').length) return;

                $('#accountList tbody tr')
                    .removeClass('action-shown')
                    .next('.action-row')
                    .remove();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#accountList tbody tr, #accountListMobile tbody tr').length) {
                    $('#accountList tbody tr.shown, #accountListMobile tbody tr.shown').each(function() {
                        var tr = $(this);
                        var table = tr.closest('table').attr('id') === 'accountList' ? dataTable :
                            dataTableMobile;
                        var row = table.row(tr);
                        if (row.child.isShown()) {
                            row.child.hide();
                            tr.removeClass('shown');
                        }
                    });
                }
            });
        });


        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteAccount');
            const form = document.getElementById('formDeleteAccount');
            const nameHolder = document.getElementById('accountName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {

            const modalMark = document.getElementById('modalMarkDefault');
            const formMark = document.getElementById('formMarkDefault');
            const nameMark = document.getElementById('markDefaultName');

            modalMark.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                formMark.action = button.getAttribute('data-url');
                nameMark.textContent = button.getAttribute('data-name');
            });

            const modalRemove = document.getElementById('modalRemoveDefault');
            const formRemove = document.getElementById('formRemoveDefault');
            const nameRemove = document.getElementById('removeDefaultName');

            modalRemove.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                formRemove.action = button.getAttribute('data-url');
                nameRemove.textContent = button.getAttribute('data-name');
            });
        });

        initRowActionHandler('#accountList');
    </script>
@endpush
