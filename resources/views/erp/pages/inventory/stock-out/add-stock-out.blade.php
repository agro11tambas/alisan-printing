@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock Out</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Warehouse</li>
                <li class="breadcrumb-item">Add Stock Out</li>
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
                    <a href="/erp/inventory/stock-out" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="stockOutForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Stock Out</span>
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
                <form action="/erp/inventory/stock-out/store/{{ $stockOut->id }}" method="POST" id="stockOutForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('POST')
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                Invoice Number :
                                <span>
                                    @if ($stockOut->note === 'Sale Account')
                                        {{ $stockOut->order_number }}
                                    @elseif($stockOut->note === 'Purchase Returns')
                                        {{ $stockOut->purchase_number }}
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
                                                <input type="hidden" name="inventory_id" value="{{ $stockOut->id }}">
                                                <input type="text" class="form-control" id="invoice_number"
                                                    name="invoice_number"
                                                    value="{{ $stockOut->note === 'Sale Account' ? $stockOut->order_number : $stockOut->purchase_number }}"
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
                                                    name="waybill_number" value="{{ $stockOut->waybill_number }}">
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
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="notes" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea class="form-control" id="notes" name="notes" placeholder="Catatan"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Add Stock Out</h4>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Stock Out</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stockOut->items as $index => $item)
                                        <tr>
                                            <td>{{ $item->product->name ?? '-' }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>
                                                <input type="hidden"
                                                    name="items[{{ $index }}][inventory_item_id]"
                                                    value="{{ $item->id }}">
                                                <input type="text" inputmode="numeric"
                                                    name="items[{{ $index }}][stock_out]" class="form-control"
                                                    value="{{ number_format($item->quantity - $item->stock_out, 0, ',', '.') }}"
                                                    min="0" max="{{ $item->quantity - $item->stock_out }}"
                                                    placeholder="Jumlah dikirim" readonly>
                                                <small class="text-muted">Sisa:
                                                    {{ number_format($item->quantity - $item->stock_out, 0, ',', '.') }}</small>
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

            // === PREVIEW IMAGE WAYBILL ===
            $('#waybill_image').on('change', function() {
                const [file] = this.files;
                if (file) {
                    $('#preview-image')
                        .attr('src', URL.createObjectURL(file))
                        .show();
                }
            });

            // === FORMAT ANGKA RIBUAN DENGAN KOMA (1,000) ===
            function formatNumber(n) {
                return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // Hapus 0 otomatis saat fokus
            $(document).on('focus', 'input[name^="items"][name$="[stock_out]"]', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            // Balik ke 0 kalau kosong
            $(document).on('blur', 'input[name^="items"][name$="[stock_out]"]', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });

            // Format ribuan real-time saat diketik
            $(document).on('input', 'input[name^="items"][name$="[stock_out]"]', function(e) {
                const input = e.target;
                const cursorPos = input.selectionStart;
                const raw = input.value.replace(/\./g, '');
                if (raw === '') return;
                const formatted = formatNumber(raw);
                const diff = formatted.length - input.value.length;
                input.value = formatted;
                input.setSelectionRange(cursorPos + diff, cursorPos + diff);
            });

            // Hapus koma saat submit
            $('#stockOutForm').on('submit', function() {
                $('input[name^="items"][name$="[stock_out]"]').each(function() {
                    this.value = this.value.replace(/\./g, '');
                });
            });
        });
    </script>
@endpush