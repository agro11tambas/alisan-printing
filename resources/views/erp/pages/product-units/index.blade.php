@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #productUnitList td.desktop-only,
            #productUnitList th.desktop-only {
                display: none !important;
            }
        }

        #productUnitList {
            width: 100% !important;
            min-width: 0;
        }

        #productUnitList_wrapper .dataTables_scrollBody {
            background-image: none !important;
            height: 60vh !important;
            overflow-y: auto !important;
        }

        .dataTables_scrollBody {
            scroll-behavior: smooth;
        }

        #productUnitList tbody tr {
            animation: fadeIn 0.3s ease-in;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Product Unit</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Product Unit</li>
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
                    <a href="/erp/products/units/create" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Product Unit</span>
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
                                <label class="fw-semibold fs-12">Search</label>
                                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                                    <div class="col-auto">
                                        <input type="text" id="search_keyword" class="form-control"
                                            placeholder="Search unit name / description"
                                            style="padding: 0.25rem 0.5rem; font-size: 0.875rem; width: 280px !important;">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-3"></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="productUnitList">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalDeleteProductUnit" tabindex="-1" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteProductUnit">
                @csrf
                @method('DELETE')

                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus Product Unit</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p>
                            Apakah Anda yakin ingin menghapus unit
                            <strong id="productUnitName"></strong>?
                        </p>
                        <p class="text-muted">Data yang dihapus tidak dapat dikembalikan.</p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger btn-md">
                            Hapus
                        </button>
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

            const dataTable = $('#productUnitList').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                ordering: false,
                order: [
                    [0, 'asc']
                ],
                data: [],
                columns: [{
                        data: 'name'
                    },
                    {
                        data: 'description'
                    },
                ]
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;

                isLoading = true;

                $.ajax({
                    url: "{{ url('/erp/products/units/data') }}",
                    type: 'GET',
                    data: {
                        start: currentPage * 50,
                        length: 50,
                        search_keyword: $('#search_keyword').val(),
                    },
                    success: function(response) {
                        if (response && response.data && response.data.length > 0) {
                            allData = allData.concat(response.data);
                            if (dataTable.rows().count() === 0) {
                                dataTable.rows.add(response.data).draw(false);
                            } else {
                                let newNodes = dataTable.rows.add(response.data).nodes();
                                $(dataTable.table().body()).append(newNodes);
                            }

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

            
            $('.dataTables_scrollBody').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                    const scrollHeight = $(this)[0].scrollHeight;
                    const clientHeight = $(this).height();

                    // Load earlier (70%) without delay
                    if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                        loadMoreData();
                    }
            });

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;

                dataTable.clear().draw();
                loadMoreData();
            }

            
            $('#search_keyword').on('input', function() {
                clearTimeout(searchTimeout);

                searchTimeout = setTimeout(function() {
                    resetAndReload();
                }, 400);
            });

            $('#productUnitList tbody').on('click', 'tr', function(e) {
                if ($(e.target).closest('td.dt-control').length) return;

                let $tr = $(this);
                let row = dataTable.row($tr);

                $('#productUnitList tbody tr').removeClass('action-shown').next('.action-row').remove();

                if ($tr.hasClass('action-shown')) {
                    $tr.removeClass('action-shown');
                } else {
                    let data = row.data();

                    if (!data || !data.action) return;

                    let actionHtml = data.action;
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
                if ($(e.target).closest('#productUnitList').length) return;

                $('#productUnitList tbody tr')
                    .removeClass('action-shown')
                    .next('.action-row')
                    .remove();
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteProductUnit');
            const form = document.getElementById('formDeleteProductUnit');
            const nameHolder = document.getElementById('productUnitName');

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
