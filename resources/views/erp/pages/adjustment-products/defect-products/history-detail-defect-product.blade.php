@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #defectHistoryTable td.desktop-only,
            #defectHistoryTable th.desktop-only {
                display: none !important;
            }
        }

        #defectHistoryTable {
            width: 100% !important;
            min-width: 0;
        }

        #defectHistoryTable_wrapper .dataTables_scrollBody {
            background-image: none !important;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Defect Product History</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Adjustment</li>
                <li class="breadcrumb-item"><a href="/erp/adjustment-products/defect-products">Defect Products</a></li>
                <li class="breadcrumb-item">History</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/adjustment-products/defect-products" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body p-0">
                        <div class="row g-0 p-2 justify-content-between">
                            <div class="col-lg-12">
                                <h5 class="fw-bold mb-2">Product: {{ $defect->product->name ?? '-' }}</h5>
                                <p><strong>Supplier:</strong> {{ $defect->supplier->name ?? '-' }}</p>
                                <p><strong>Remaining Defect Stock:</strong> <span
                                        class="text-danger">{{ number_format($defect->quantity) }}</span></p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="defectHistoryTable">
                                <thead>
                                    <tr>
                                        <th class="wd-30">No</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>Quantity</th>
                                        <th>Note</th>
                                        <th>Created By</th>
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

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#defectHistoryTable').DataTable({
                processing: true,
                serverSide: true,
                scrollY: 600,
                deferRender: true,
                paging: true,
                searching: false,
                lengthChange: false,
                info: false,
                ajax: "{{ url('/erp/adjustment-products/defect-products/history/data/' . $defect->id) }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action_date',
                        name: 'action_date'
                    },
                    {
                        data: 'action_type',
                        name: 'action_type'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'note',
                        name: 'note'
                    },
                    {
                        data: 'user',
                        name: 'user'
                    },
                ]
            });
        });
    </script>
@endpush
