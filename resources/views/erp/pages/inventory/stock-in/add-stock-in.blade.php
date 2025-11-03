@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock In</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Warehouse</li>
                <li class="breadcrumb-item">Add Stock In</li>
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
                    <a href="/erp/inventory/stock-in" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="stockInForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Stock In</span>
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
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <form action="/erp/inventory/stock-in/store/{{ $stockIn->id }}" method="POST" id="stockInForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                Invoice Number :
                                <span>
                                    @if ($stockIn->note === 'Sale Returns')
                                        {{ $stockIn->order_number }}
                                    @elseif($stockIn->note === 'Purchase Account')
                                        {{ $stockIn->purchase_number }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="invoice_number" class="fw-semibold">Invoice Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="hidden" name="inventory_id" value="{{ $stockIn->id }}">
                                                <input type="text" class="form-control" id="invoice_number"
                                                    name="invoice_number"
                                                    value="{{ $stockIn->note === 'Sale Returns' ? $stockIn->order_number : $stockIn->purchase_number }}"
                                                    readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="change_date" class="fw-semibold">Change Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="change_date"
                                                    name="change_date" value="{{ now()->format('Y-m-d') }}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="waybill_number" class="fw-semibold">Waybill Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="waybill_number"
                                                    name="waybill_number" value="{{ $stockIn->waybill_number }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="waybill_image" class="fw-semibold">Waybill Image</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="waybill_image"
                                                    name="waybill_image" accept="image/*"
                                                    value="{{ old('waybill_image') }}">
                                            </div>
                                            <img id="preview-image" src="#" alt="Preview"
                                                style="display:none; max-width: 100px; margin-top: 10px; border-radius: 10px" />
                                        </div>
                                    </div>
                                    {{-- <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="notes" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea class="form-control" id="notes" name="notes" placeholder="Catatan"></textarea>
                                            </div>
                                        </div>
                                    </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Add Stock In</h4>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Stock In</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stockIn->items as $index => $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][inventory_item_id]"
                                                    value="{{ $item->id }}">
                                                <input type="text" inputmode="numeric"
                                                    name="items[{{ $index }}][stock_in]" class="form-control"
                                                    value="0" min="0"
                                                    max="{{ $item->quantity - $item->stock_in }}"
                                                    placeholder="Jumlah dikirim">
                                                <small class="text-muted">Sisa:
                                                    {{ number_format($item->quantity - $item->stock_in, 0, ',', '.') }}</small>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $index }}][notes]"
                                                    class="form-control" placeholder="Catatan">
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
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            $('#waybill_image').on('change', function() {
                const [file] = this.files;
                if (file) {
                    $('#preview-image')
                        .attr('src', URL.createObjectURL(file))
                        .show();
                }
            });

            function formatNumber(n) {
                return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            $(document).on('focus', 'input[name^="items"][name$="[stock_in]"]', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            $(document).on('blur', 'input[name^="items"][name$="[stock_in]"]', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });

            $(document).on('input', 'input[name^="items"][name$="[stock_in]"]', function() {
                const input = $(this);
                const raw = input.val().replace(/\./g, '');
                if (raw === '') return;

                let value = parseInt(raw) || 0;
                const max = parseInt(input.attr('max')) || 0;

                if (value > max) {
                    value = max;
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'warning',
                        title: 'Jumlah tidak boleh melebihi total sisa (' + max.toLocaleString(
                            'id-ID') + ')',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }

                input.val(value.toLocaleString('id-ID'));
            });

            $('#stockInForm').on('submit', function(e) {
                let isValid = true;

                // 🔹 Hapus error lama
                $(this).find('.is-invalid').removeClass('is-invalid');
                $(this).find('.invalid-feedback').remove();

                // 🔹 Hapus titik pemisah sebelum validasi numerik
                $('input[name^="items"][name$="[stock_in]"]').each(function() {
                    this.value = this.value.replace(/\./g, '');
                });

                // 🔹 Fungsi tampilkan error
                function showError(element, message) {
                    $(element).addClass('is-invalid');
                    $(element).after(
                        '<div class="invalid-feedback d-block text-danger fw-semibold small">' +
                        message + '</div>');
                }

                // 🔹 Validasi setiap kolom Stock In
                $('input[name^="items"][name$="[stock_in]"]').each(function() {
                    const val = parseInt($(this).val().trim() || 0);
                    if (val <= 0) {
                        isValid = false;
                        showError(this, 'Harus diisi lebih dari 0');
                    }
                });

                // 🔹 Validasi Waybill Number
                const waybillNumber = $('#waybill_number');
                if (!waybillNumber.val().trim()) {
                    isValid = false;
                    showError(waybillNumber[0], 'Waybill number wajib diisi');
                }

                // 🔹 Validasi Waybill Image
                // const waybillImage = $('#waybill_image');
                // if (!waybillImage[0].files.length) {
                //     isValid = false;
                //     showError(waybillImage[0], 'Gambar waybill wajib diunggah');
                // }

                // 🔹 Jika tidak valid, cegah submit
                if (!isValid) {
                    e.preventDefault();
                    // Scroll ke elemen pertama yang error
                    $('html, body').animate({
                        scrollTop: $('.is-invalid:first').offset().top - 100
                    }, 500);
                }
            });
        });
    </script>
@endpush
