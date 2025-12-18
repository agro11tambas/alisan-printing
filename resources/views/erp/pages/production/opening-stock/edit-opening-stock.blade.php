@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Opening Stock Production</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Opening Stock Production</li>
                <li class="breadcrumb-item">Create Opening Stock Production</li>
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
                    <a href="/erp/opening-stock-rate" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="openingStockRateForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Opening Stock Production</span>
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
                text: "{{ session('error') }}",
            });
        </script>
    @endif
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-2 pt-md-0">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form action="/erp/productions/opening-stock/update" method="POST" id="openingStockRateForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body px-0 py-4">
                            <div class="row px-4 mb-3">
                                <div class="col-lg-12">
                                    <h4>Opening Stock & Rate</h4>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table" id="openingBalanceList">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Product Name</th>
                                            <th>Opening Stock</th>
                                            <th>Finished Product</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $no = 1; @endphp
                                        @foreach ($openingStockProductions as $stock)
                                            <tr>
                                                <td>{{ $no++ }}</td>
                                                <td>
                                                    {{ $stock->product->name }}
                                                    {{-- tambahkan hidden id --}}
                                                    <input type="hidden" name="id[]" value="{{ $stock->id }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control" name="opening_stock[]"
                                                        value="{{ number_format($stock->opening_stock, 0, ',', '.') }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control"
                                                        name="opening_finished_product_stock[]"
                                                        value="{{ number_format($stock->opening_finished_product_stock, 0, ',', '.') }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function formatNumberID(value) {
            if (!value) return '';
            const num = value.toString().replace(/\D/g, '');
            return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function unformatNumberID(value) {
            return value ? value.toString().replace(/\./g, '') : '';
        }

        document.addEventListener('input', function(e) {
            if (e.target.matches('input[name="opening_stock[]"], input[name="opening_finished_product_stock[]"]')) {
                const raw = e.target.value.replace(/\D/g, '');
                e.target.value = formatNumberID(raw);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll(
                'input[name="opening_stock[]"], input[name="opening_finished_product_stock[]"]').forEach(el => {
                if (el.value.trim() !== '') el.value = formatNumberID(el.value);
            });
        });

        document.getElementById('openingStockRateForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

            let isValid = true;

            form.querySelectorAll('tbody tr').forEach(row => {
                const openingStock = row.querySelector('input[name="opening_stock[]"]');
                const finishedStock = row.querySelector('input[name="opening_finished_product_stock[]"]');

                if (!openingStock.value.trim()) {
                    showError(openingStock, 'Opening stock wajib diisi');
                    isValid = false;
                }
                if (!finishedStock.value.trim()) {
                    showError(finishedStock, 'Finished product wajib diisi');
                    isValid = false;
                }
            });

            if (!isValid) return;

            form.querySelectorAll('input[name="opening_stock[]"], input[name="opening_finished_product_stock[]"]')
                .forEach(input => {
                    input.value = unformatNumberID(input.value);
                });

            form.submit();
        });

        function showError(input, message) {
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block';
            feedback.textContent = message;
            input.closest('div').appendChild(feedback);
        }

        document.addEventListener('input', function(e) {
            if (e.target.matches('input.is-invalid')) {
                e.target.classList.remove('is-invalid');
                const feedback = e.target.parentNode.querySelector('.invalid-feedback');
                if (feedback) feedback.remove();
            }
        });
    </script>
@endpush
