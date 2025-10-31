@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Edit Opening Stock Overview</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/opening-stock">Opening Stock Overview</a></li>
                <li class="breadcrumb-item active">Edit Opening Stock Overview</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items d-flex align-items-center gap-2">
                <a href="/erp/opening-stock" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i><span>Back</span>
                </a>
                <button type="submit" form="openingStockOverviewForm" class="btn btn-primary">
                    <i class="feather-check me-2"></i><span>Update Opening Stock</span>
                </button>
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
                text: "{{ session('error') }}"
            });
        </script>
    @endif

    <div class="main-content">
        <div class="row">
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <form id="openingStockOverviewForm" action="/erp/opening-stock/update" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="card-body">
                            <h5 class="mb-3">Opening Stock Overview (Inventory + Production)</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No</th>
                                            <th>Product</th>
                                            <th class="text-end">Opening Stock (Inventory)</th>
                                            <th class="text-end">Opening Stock (Production)</th>
                                            <th class="text-end">Opening Rate</th>
                                            <th class="text-end">Minimum Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $no = 1; @endphp
                                        @foreach ($openingStockRates as $inv)
                                            @php
                                                $prod = $openingStockProductions->firstWhere(
                                                    'product_id',
                                                    $inv->product_id,
                                                );
                                            @endphp
                                            <tr>
                                                <td>{{ $no++ }}</td>
                                                <td>
                                                    {{ $inv->product->name }}
                                                    <input type="hidden" name="inv_id[]" value="{{ $inv->id }}">
                                                    <input type="hidden" name="prod_id[]" value="{{ $prod->id ?? '' }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control text-end"
                                                        name="opening_stock[]"
                                                        value="{{ $inv->opening_stock !== null ? number_format($inv->opening_stock, 0, ',', '.') : '' }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control text-end"
                                                        name="opening_stock_production[]"
                                                        value="{{ $prod && $prod->opening_stock !== null ? number_format($prod->opening_stock, 0, ',', '.') : '' }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control text-end"
                                                        name="opening_rate[]"
                                                        value="{{ $inv->opening_rate !== null ? number_format($inv->opening_rate, 2, ',', '.') : '' }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control text-end"
                                                        name="minimum_stock[]"
                                                        value="{{ $inv->minimum_stock !== null ? number_format($inv->minimum_stock, 0, ',', '.') : '' }}">
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
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector('#openingStockOverviewForm');

            /**
             * Format angka Indonesia (ribuan pakai titik, desimal pakai koma)
             */
            function formatNumberID(value, allowDecimal = false) {
                if (!value) return '';
                value = value.toString();

                // izinkan koma hanya jika allowDecimal = true
                value = allowDecimal ? value.replace(/[^\d,]/g, '') : value.replace(/\D/g, '');

                const parts = value.split(',');

                // format bagian ribuan (sebelum koma)
                let integerPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                // jika ada koma, gabungkan kembali
                return allowDecimal && parts.length > 1 ? `${integerPart},${parts[1]}` : integerPart;
            }

            /**
             * Hapus format (ubah jadi float untuk backend)
             */
            function unformatNumber(value) {
                if (!value) return '0';
                return value.toString()
                    .replace(/\./g, '') // hapus titik ribuan
                    .replace(',', '.'); // ubah koma jadi titik desimal
            }

            /**
             * Format realtime input
             */
            document.addEventListener('input', function(e) {
                if (!e.target.matches('input[type="text"]')) return;

                const name = e.target.getAttribute('name');

                // kalau kolom opening_rate[] → izinkan koma
                const allowDecimal = name === 'opening_rate[]';
                const cursorPos = e.target.selectionStart;

                e.target.value = formatNumberID(e.target.value, allowDecimal);

                // restore posisi kursor agar input nyaman
                e.target.setSelectionRange(cursorPos, cursorPos);
            });

            /**
             * Submit form
             */
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                let isValid = true;
                form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

                form.querySelectorAll('input[type="text"]').forEach(input => {
                    const name = input.getAttribute('name');
                    const allowDecimal = name === 'opening_rate[]';
                    if (!input.value.trim()) {
                        isValid = false;
                        input.classList.add('is-invalid');
                        const feedback = document.createElement('div');
                        feedback.className = 'invalid-feedback d-block';
                        feedback.textContent = 'Kolom ini wajib diisi';
                        input.closest('td').appendChild(feedback);
                    } else {
                        input.value = unformatNumber(input.value);
                    }
                });

                if (isValid) form.submit();
            });

            /**
             * Hapus invalid saat diketik ulang
             */
            document.addEventListener('input', function(e) {
                if (e.target.matches('.is-invalid')) {
                    e.target.classList.remove('is-invalid');
                    const feedback = e.target.closest('td').querySelector('.invalid-feedback');
                    if (feedback) feedback.remove();
                }
            });
        });
    </script>
@endpush
