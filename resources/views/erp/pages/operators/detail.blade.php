@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Operator Detail</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/shop-manager/operators">Operators</a></li>
                <li class="breadcrumb-item active">{{ $operator->name }}</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <a href="/erp/shop-manager/operators" class="btn btn-light">
                <i class="feather-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            <i class="feather-user me-2"></i>{{ $operator->name }}
                        </h6>
                        <span class="badge {{ $operator->active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $operator->active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="card-body">
                        @if ($products->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th class="text-end">Completed</th>
                                            <th class="text-end">Defect</th>
                                            <th class="text-end">Reject</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($products as $i => $p)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $p['product_name'] }}</td>
                                                <td>{{ $p['sku'] }}</td>
                                                <td class="text-end text-success fw-bold">
                                                    {{ number_format($p['completed']) }}</td>
                                                <td class="text-end text-warning fw-bold">{{ number_format($p['defect']) }}
                                                </td>
                                                <td class="text-end text-danger fw-bold">{{ number_format($p['reject']) }}
                                                </td>
                                                <td class="text-end fw-bold">
                                                    {{ number_format($p['completed'] + $p['defect'] + $p['reject']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted mb-0">No product progress history found for this operator.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
