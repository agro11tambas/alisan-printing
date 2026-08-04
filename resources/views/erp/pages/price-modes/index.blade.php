@extends('erp.layouts.main')

@push('styles')
    <style>
        #modeList { width: 100% !important; min-width: 0; }
        #modeList_wrapper .dataTables_scrollBody {
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
        #modeList tbody tr { animation: fadeIn .3s ease-in; }
        @media (max-width: 768px) {
            #modeList .desktop-only { display: none !important; }
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title"><h5 class="m-b-10">Mode</h5></div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Mode</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="/erp/products/price-modes/create" class="btn btn-primary">
                <i class="feather-plus me-2"></i><span>Create Mode</span>
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if (session('success') || session('error'))
        <script>
            Swal.fire({
                icon: '{{ session('error') ? 'error' : 'success' }}',
                title: '{{ session('error') ? 'Gagal!' : 'Berhasil!' }}',
                text: @json(session('error') ?: session('success')),
            });
        </script>
    @endif

    <div class="main-content m-0 m-md-2 p-0 pt-1">
        <div class="row"><div class="col-lg-12">
            <div class="card stretch stretch-full">
                <div class="card-body p-0">
                    <div class="row g-3 p-2 justify-content-between">
                        <div class="col-lg-4 me-2">
                            <label class="fw-semibold fs-12">Search</label>
                            <input type="text" id="search_keyword" class="form-control"
                                placeholder="Search mode name / code"
                                style="padding:.25rem .5rem;font-size:.875rem;width:280px!important">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover bg-transparent" id="modeList">
                            <thead><tr>
                                <th>Name</th><th>Code</th>
                                <th class="desktop-only">Status</th>
                            </tr></thead>
                        </table>
                    </div>
                </div>
            </div>
        </div></div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalDeleteMode" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteMode">
                @csrf @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">Hapus Mode</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus mode <strong id="modeName"></strong>?</p>
                        <p class="text-muted mb-0">Mode yang sudah dipakai tidak dapat dihapus. Nonaktifkan bila diperlukan.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let currentPage = 0, isLoading = false, hasMoreData = true, searchTimeout;
            const table = $('#modeList').DataTable({
                processing: false, serverSide: false, scrollY: '60vh', scrollCollapse: true,
                paging: false, searching: false, info: false, lengthChange: false, ordering: false,
                data: [],
                columns: [
                    { data: 'name' }, { data: 'code' },
                    { data: 'status', className: 'desktop-only' },
                ]
            });

            function loadMore() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;
                $.get(@json(url('/erp/products/price-modes/data')), {
                    start: currentPage * 50, length: 50, search_keyword: $('#search_keyword').val()
                }).done(function(response) {
                    if (response.data?.length) {
                        table.rows.add(response.data).draw(false);
                        currentPage++;
                    } else hasMoreData = false;
                    if (!response.has_more) hasMoreData = false;
                }).fail(function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Error loading data.', 'error');
                }).always(function() { isLoading = false; });
            }

            function reload() {
                currentPage = 0; hasMoreData = true; table.clear().draw(); loadMore();
            }

            loadMore();
            $('.dataTables_scrollBody').on('scroll', function() {
                if ($(this).scrollTop() + $(this).height() >= this.scrollHeight * .7) loadMore();
            });
            $('#search_keyword').on('input', function() {
                clearTimeout(searchTimeout); searchTimeout = setTimeout(reload, 400);
            });
            $('#modeList tbody').on('click', 'tr', function() {
                const row = table.row(this), data = row.data();
                $('#modeList tbody tr').removeClass('action-shown').next('.action-row').remove();
                if (!data?.action) return;
                const colCount = $(this).find('td').length;
                $(this).after('<tr class="action-row"><td colspan="' + colCount +
                    '"><div class="d-flex justify-content-center">' + data.action + '</div></td></tr>');
                $(this).addClass('action-shown');
            });
        });

        document.getElementById('modalDeleteMode')?.addEventListener('show.bs.modal', function(event) {
            document.getElementById('formDeleteMode').action = event.relatedTarget.dataset.url;
            document.getElementById('modeName').textContent = event.relatedTarget.dataset.name;
        });
    </script>
@endpush
