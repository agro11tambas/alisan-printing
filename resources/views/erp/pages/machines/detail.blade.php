@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Mesin Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/productions/machines">Mesin</a></li>
                <li class="breadcrumb-item active">{{ $machine->name }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="/erp/productions/machines" class="btn btn-light">
                <i class="feather-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="feather-cpu me-2"></i>{{ $machine->name }}
                        </h6>
                        <span class="badge {{ $machine->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $machine->active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="card-body">

                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="MachineProductTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th class="text-end">Completed</th>
                                        <th class="text-end">Defect</th>
                                        <th class="text-end">Reject</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div id="loadingIndicator" class="text-center text-muted py-1" style="display:none;">
                                Loading...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const machineId = {{ $machine->id }};
            let allData = [];
            let currentPage = 0;
            let isLoading = false;
            let hasMoreData = true;
            let filter = '';
            let startDate = '';
            let endDate = '';

            const table = $('#MachineProductTable').DataTable({
                processing: false,
                serverSide: false,
                scrollY: '60vh',
                scrollCollapse: true,
                paging: false,
                searching: false,
                info: false,
                lengthChange: false,
                data: [],
                columns: [{
                        data: 'last_date'
                    },
                    {
                        data: 'product_name'
                    },
                    {
                        data: 'sku'
                    },
                    {
                        data: 'completed',
                        className: 'text-end text-success fw-bold',
                        render: d => d.toLocaleString()
                    },
                    {
                        data: 'defect',
                        className: 'text-end text-warning fw-bold',
                        render: d => d.toLocaleString()
                    },
                    {
                        data: 'reject',
                        className: 'text-end text-danger fw-bold',
                        render: d => d.toLocaleString()
                    },
                    {
                        data: 'total',
                        className: 'text-end fw-bold',
                        render: d => d.toLocaleString()
                    },
                ]
            });

            function loadMoreData() {
                if (isLoading || !hasMoreData) return;
                isLoading = true;
                $('#loadingIndicator').show();

                $.ajax({
                    url: `/erp/productions/machines/detail/${machineId}/data`,
                    type: 'GET',
                    data: {
                        start: currentPage * 15,
                        length: 15,
                        filter: filter,
                        start_date: startDate,
                        end_date: endDate
                    },
                    success: function(res) {
                        if (res.data.length > 0) {
                            allData = allData.concat(res.data);
                            table.clear().rows.add(allData).draw(false);
                            currentPage++;
                        } else {
                            hasMoreData = false;
                            $('#loadingIndicator').html('✅ All data loaded').show();
                            setTimeout(() => $('#loadingIndicator').hide(), 2000);
                        }
                        isLoading = false;
                        $('#loadingIndicator').hide();
                    },
                    error: function() {
                        isLoading = false;
                        $('#loadingIndicator').hide();
                    }
                });
            }

            // Initial load
            loadMoreData();

            // Infinite scroll
            $('.dataTables_scrollBody').on('scroll', function() {
                const scrollTop = $(this).scrollTop();
                const scrollHeight = $(this)[0].scrollHeight;
                const clientHeight = $(this).height();

                // Load earlier (70%) without delay
                if (scrollTop + clientHeight >= scrollHeight * 0.70) {
                    loadMoreData();
                }
            });

            // Filter tanggal
            $('#filter').on('change', function() {
                filter = $(this).val();
                if (filter === 'custom') {
                    $('#start_date, #end_date').prop('disabled', false);
                } else {
                    $('#start_date, #end_date').val('').prop('disabled', true);
                    resetAndReload();
                }
            });

            $('#start_date, #end_date').on('change', function() {
                startDate = $('#start_date').val();
                endDate = $('#end_date').val();
                if (filter === 'custom' && startDate && endDate) resetAndReload();
            });

            function resetAndReload() {
                allData = [];
                currentPage = 0;
                hasMoreData = true;
                table.clear().draw();
                loadMoreData();
            }
        });
    </script>
@endpush
