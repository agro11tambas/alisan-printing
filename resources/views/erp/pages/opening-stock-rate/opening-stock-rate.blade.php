@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #openingBalanceList td.desktop-only,
            #openingBalanceList th.desktop-only {
                display: none !important;
            }
        }

        #openingBalanceList {
            width: 100% !important;
            min-width: 0;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Opening Balance</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Opening Balance</li>
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
                    <a href="/erp/opening-stock-rate/edit-opening-stock-rate" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Opening Balance</span>
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
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body px-0 py-4">
                        <div class="row px-4 mb-3">
                            <div class="col-lg-12">
                                <h4>Opening Stock & Rate</h4>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover bg-transparent" id="openingBalanceList">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Product Name</th>
                                        <th>Opening Stock</th>
                                        <th>Opening Rate</th>
                                        <th>Minimum Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $no = 1;
                                    @endphp
                                    @foreach ($openingStockRates as $openingStockRate)
                                        <tr>
                                            <td>{{ $no++ }}</td>
                                            <td>{{ $openingStockRate->product ? $openingStockRate->product->name : '-' }}
                                            </td>
                                            <td>{{ number_format($openingStockRate->opening_stock, 0, ',', '.') }}</td>
                                            <td>{{ number_format($openingStockRate->opening_rate, 2, ',', '.') }}</td>
                                            <td>{{ number_format($openingStockRate->minimum_stock, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
