@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #discountList td.desktop-only,
            #discountList th.desktop-only {
                display: none !important;
            }
        }

        #discountList {
            width: 100% !important;
            min-width: 0;
        }

        #discountList_wrapper .dataTables_scrollBody {
            /* background: #fff !important; */
            background-image: none !important;
        }

        /* DataTables mereservasi ruang scrollbar di tabel header lewat padding-right,
           padahal body-nya belum tentu punya scrollbar -> sisa ruang kosong di ujung thead. */
        #discountList_wrapper .dataTables_scrollHeadInner {
            width: 100% !important;
            padding-right: 0 !important;
        }

        #discountList_wrapper .dataTables_scrollHeadInner>table {
            width: 100% !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Discounts</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Discounts</li>
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
                    <a href="/erp/discounts/create-discount" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Discount</span>
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
                                <form id="filter-form" class="row g-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="fw-semibold fs-12">Start Date</label>
                                        <input type="date" id="start_date" name="start_date" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="fw-semibold fs-12">Due Date</label>
                                        <input type="date" id="end_date" name="end_date" class="form-control"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                    </div>
                                </form>
                            </div>
                            <div class="col-lg-3">

                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="discountList">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Min Based On</th>
                                        <th>Min Qty or Purchase Amount</th>
                                        <th>Apply On</th>
                                        <th>Status</th>
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
    <div class="modal fade" id="modalDeleteDiscount" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteDiscount">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Discount</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus Discount <strong id="discountName"></strong>?</p>
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
            if ($.fn.DataTable.isDataTable('#discountList')) {
                $('#discountList').DataTable().clear().destroy();
            }

            const dataTable = $('#discountList').DataTable({
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
                    url: "{{ url('/erp/discounts/data') }}",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
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
                        data: 'type',
                        name: 'type',
                    },
                    {
                        data: 'amount',
                        name: 'amount',
                    },
                    {
                        data: 'minimum_based_on',
                        name: 'minimum_based_on',
                    },
                    {
                        data: 'minimum_qty_or_amount',
                        name: 'minimum_qty_or_amount',
                    },
                    {
                        data: 'apply_on',
                        name: 'apply_on',
                    },
                    {
                        data: 'is_active',
                        name: 'is_active',
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

            $('#start_date, #end_date').on('change', function() {
                dataTable.draw();
            });

            $('#discountList tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#discountList tbody tr').removeClass('action-shown').next('.action-row').remove();

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
                if ($(e.target).closest('#discountList').length) return;

                $('#discountList tbody tr').removeClass('action-shown').next('.action-row').remove();
            });

            $(document).on('click', function(e) {
                if (!$(e.target).closest('#discountList tbody tr, #discountListMobile tbody tr').length) {
                    $('#discountList tbody tr.shown, #discountListMobile tbody tr.shown').each(function() {
                        var tr = $(this);
                        var table = tr.closest('table').attr('id') === 'discountList' ? dataTable :
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
            const modal = document.getElementById('modalDeleteDiscount');
            const form = document.getElementById('formDeleteDiscount');
            const nameHolder = document.getElementById('discountName');

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
