@extends('erp.layouts.main')

@push('styles')
    <style>
        .stock-reset-option {
            border: 1px solid var(--bs-border-color);
            border-radius: 10px;
            padding: 14px 16px;
        }

        .stock-reset-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(220, 53, 69, 0.12);
            color: #dc3545;
            font-size: 28px;
        }

        #resetStockModal .modal-content {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 18px 55px rgba(0, 0, 0, 0.22);
        }

        #resetStockModal .modal-header,
        #resetStockModal .modal-footer {
            border: 0;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Reset Stock</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Reset Stock</li>
            </ul>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="card stretch stretch-full border-danger">
                    <div class="card-header d-flex align-items-center gap-3">
                        <span class="stock-reset-icon" style="width: 46px; height: 46px; font-size: 20px;">
                            <i class="feather-refresh-cw"></i>
                        </span>
                        <div>
                            <h5 class="fw-bold mb-1">Reset Stock</h5>
                            <span class="text-muted">Kembalikan seluruh nilai stock menjadi 0.</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning d-flex align-items-start gap-2">
                            <i class="feather-alert-triangle mt-1"></i>
                            <span>Histori transaksi dan data stock lainnya tidak akan diubah.</span>
                        </div>

                        <div class="d-grid gap-2 mb-4">
                            <div class="stock-reset-option d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="feather-package text-danger fs-5"></i>
                                    <strong>Production Stock</strong>
                                </div>
                                <span class="badge bg-light text-dark">
                                    {{ number_format($productionStockCount, 0, ',', '.') }} data
                                </span>
                            </div>
                            <div class="stock-reset-option d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="feather-archive text-danger fs-5"></i>
                                    <strong>Inventory Stock</strong>
                                </div>
                                <span class="badge bg-light text-dark">
                                    {{ number_format($inventoryStockCount, 0, ',', '.') }} data
                                </span>
                            </div>
                        </div>

                        <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                            data-bs-target="#resetStockModal">
                            <i class="feather-refresh-cw me-2"></i>Reset Stock
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="resetStockModal" tabindex="-1" aria-labelledby="resetStockModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('stock-reset.reset') }}" class="modal-content">
                @csrf
                <input type="hidden" name="confirmation" value="1">

                <div class="modal-header pb-0">
                    <span></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-center px-4 pt-2 pb-4">
                    <span class="stock-reset-icon mb-3">
                        <i class="feather-alert-triangle"></i>
                    </span>
                    <h4 class="fw-bold mb-2" id="resetStockModalLabel">Reset seluruh stock?</h4>
                    <p class="text-muted mb-4">
                        Nilai Production Stock dan Inventory Stock akan diubah menjadi 0. Tindakan ini tidak dapat dibatalkan.
                    </p>

                    <div class="rounded-3 bg-light p-3 text-start">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Production Stock</span>
                            <strong>0</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Inventory Stock</span>
                            <strong>0</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer px-4 pb-4 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="feather-refresh-cw me-2"></i>Reset Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resetStockModal = document.getElementById('resetStockModal');

            if (resetStockModal && resetStockModal.parentElement !== document.body) {
                document.body.appendChild(resetStockModal);
            }
        });
    </script>
@endpush
