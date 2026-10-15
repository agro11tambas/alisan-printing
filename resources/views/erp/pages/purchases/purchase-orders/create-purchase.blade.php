@extends('erp.layouts.main')

@push('styles')
    <style>
        /* 🔹 Perbesar font di dalam Select2 container */
        .select2-container--bootstrap-5 .select2-selection--single {
            height: 42px !important;
            font-size: 16px !important;
            line-height: 42px !important;
        }

        .select2-selection__rendered {
            font-size: 16px !important;
            line-height: 42px !important;
            padding-left: 10px !important;
        }

        .select2-results__option {
            font-size: 16px !important;
            padding: 4px 8px !important;
        }

        .select2-selection__arrow {
            height: 42px !important;
            right: 10px !important;
        }
        @include('erp.pages.partials.transaction-form-mobile-styles')
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Purchase Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase Order</li>
                <li class="breadcrumb-item">Create Purchase Order</li>
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
                    <a href="/erp/purchases/purchase-orders" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="purchaseForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Purchase Order</span>
                    </button>
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
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: @json(session('error')),
            });
        </script>
    @endif
    <div class="main-content transaction-form-page m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row">
            <div class="col-12">
                {{-- PO hanya wadah grouping per supplier. Produk, harga, dan
                     tujuan stok diisi di Purchase List anaknya. --}}
                <form action="/erp/purchases/purchase-orders/store" method="POST" id="purchaseForm">
                    @csrf
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row mb-2 align-items-center">
                                <div class="col-lg-2">
                                    <label for="suppliers" class="fw-semibold">Supplier:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <select class="form-select form-control" id="suppliers" name="suppliers" required>
                                        <option value="" disabled selected hidden>Choose supplier</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">
                                                {{ $supplier->name }}{{ $suppliersWithOrder->has($supplier->id) ? ' (sudah punya PO)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">
                                        Satu supplier hanya punya satu Purchase Order. Kalau supplier sudah punya PO,
                                        kamu akan langsung diarahkan ke Purchase List-nya.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @include('erp.pages.partials.transaction-mobile-actions', [
                        'backUrl' => '/erp/purchases/purchase-orders',
                        'formId' => 'purchaseForm',
                        'submitLabel' => 'Store Purchase Order',
                    ])
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#suppliers').select2({
                theme: 'bootstrap-5',
                placeholder: 'Choose supplier',
                width: '100%',
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
