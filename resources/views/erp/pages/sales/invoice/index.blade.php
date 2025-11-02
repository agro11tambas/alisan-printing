@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale</li>
                <li class="breadcrumb-item">Invoice</li>
            </ul>
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
        <div class="row g-3">
            <div class="col-lg-12">
                <div class="card invoice-container" id="invoiceContent">
                    <div class="card-body p-0">
                        <div class="px-4 pt-4">
                            <div class="row justify-content-between">
                                <div class="col-lg-4">
                                    @php
                                        $logoPath = $invoice->logo ? str_replace('public/', '', $invoice->logo) : null;
                                    @endphp

                                    @if ($logoPath)
                                        <img src="{{ asset($logoPath) }}" alt="Logo"
                                            style="max-height: 50px; max-width: 200px; object-fit: contain;">
                                    @endif
                                    <address class="text-muted mt-2">
                                        {{ $invoice->address }}
                                    </address>
                                </div>
                                <div class="col-lg-4">
                                    <h2 class="fs-4 fw-bold text-primary">Invoice</h2>
                                    <div>
                                        <span class="fw-bold text-dark">Invoice No:</span>
                                        <span class="fw-bold text-primary">{{ $order->order_number }}</span>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark">Invoice Date:</span>
                                        <span class="text-muted">{{ date('d M Y', strtotime($order->order_date)) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="border-dashed">
                        <div class="px-4 py-sm-4">
                            <div class="row gap-4 justify-content-between">
                                <div class="col-lg-12">
                                    <h2 class="fs-16 fw-bold text-dark mb-3">Invoiced To:</h2>
                                    <address class="text-muted lh-lg">
                                        {{ $order->customer->name }}<br>
                                        {{ $order->customer->phone }}<br>
                                        {{ $order->shipping_address }}
                                    </address>
                                </div>
                                <!-- <div class="border-end border-end-dashed border-gray-500 d-none d-sm-block"></div> -->
                                <!-- <div class="col-lg-4">
                                    <h2 class="fs-16 fw-bold text-dark mb-3">Payment Details:</h2>
                                    <div class="text-muted lh-lg">
                                        <div>
                                            <span class="text-muted">Total Due: </span>
                                            <span class="fw-bold text-dark">Rp. {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted">Bank:</span>
                                            <span class="fw-bold text-success">{{ $invoice->bank_name }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted">No Rek:</span>
                                            <span class="fw-bold text-dark">{{ $invoice->account_number }}</span>
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                        <hr class="border-dashed mb-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>QTY</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $items = $order->orderItems ?? ($return->items ?? collect());
                                    @endphp

                                    @foreach ($items as $item)
                                        <tr>
                                            <td>
                                                @if ($item->product)
                                                    {{ $item->product->name }}
                                                @endif

                                                @if ($item->productBundle)
                                                    {{ $item->productBundle->name }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($item->product)
                                                    Rp. {{ number_format($item->product->price, 0, ',', '.') }}
                                                @endif

                                                @if ($item->productBundle)
                                                    Rp. {{ number_format($item->productBundle->price, 0, ',', '.') }}
                                                @endif
                                            </td>
                                            <td>{{ $item->quantity }}</td>
                                            <td class="text-end">Rp. {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="2" class="border-0"></td>
                                        <td class="fw-semibold text-dark text-lg-end border-0 text-end">Sub Total</td>
                                        <td class="fw-bold text-dark border-0 text-end">Rp.
                                            {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-0"></td>
                                        <td class="fw-semibold text-dark text-lg-end border-0 text-end">Discount</td>
                                        <td class="fw-bold text-danger border-0 text-end">- Rp.
                                            {{ number_format($order->discount, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="border-0"></td>
                                        <td class="fw-semibold text-dark text-lg-end border-0 text-end">Grand Total</td>
                                        <td class="fw-bold text-success border-0 text-end">Rp.
                                            {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <hr class="border-dashed mt-0">
                        <div class="px-4">
                            <div class="alert alert-dismissible p-4 mt-3 alert-soft-primary-message" role="alert">
                                <h5 class="mb-4">Syarat & Ketentuan</h5>
                                <p class="mb-0">
                                    @forelse($invoice->termAndConditions as $term)
                                        <i class="feather feather-alert-circle me-2"></i> {{ $term->content }}<br>
                                    @empty
                                        <i class="feather feather-alert-circle me-2"></i> Tidak ada Syarat & Ketentuan.<br>
                                    @endforelse
                                </p>
                            </div>
                        </div>
                        <div class="px-4 pt-4 d-sm-flex align-items-center justify-content-between">
                            <div class="mb-5 mb-sm-0">
                                <h6 class="fs-14 fw-bold">Bank</h6>
                                <p class="fs-14">BANK: {{ $invoice->bank_name }}<br>
                                    Atas Nama: <strong class="text-success">{{ $invoice->name }}</strong><br>
                                    No Rek: <strong class="text-danger">{{ $invoice->account_number }}</strong></p>
                            </div>
                            <div class="text-end align-self-end">
                                <h6 class="fs-13 fw-bold mt-2">Owner</h6>
                                <p class="fs-11 fw-semibold text-muted">
                                    {{ date('d M Y, H:i', strtotime($order->created_at)) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card align-items-center bg-transparent border-0 shadow-none">
                    <div class="card-body d-flex gap-3">
                        <button class="btn btn-success" id="shareInvoiceBtn">
                            <i class="feather-share-2 me-2"></i> Share ke WhatsApp
                        </button>
                        <button class="btn btn-primary" id="downloadInvoiceBtn">
                            <i class="feather-download me-2"></i> Download PNG
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        async function captureInvoice() {
            const original = document.getElementById('invoiceContent');

            const clone = original.cloneNode(true);
            clone.id = 'invoiceCloneForShare';
            clone.style.width = '768px';
            clone.style.maxWidth = '768px';
            clone.style.fontSize = '14px';
            clone.style.position = 'absolute';
            clone.style.top = '-9999px';
            clone.style.left = '-9999px';
            clone.style.background = '#fff';

            document.body.appendChild(clone);

            // Render ke canvas
            const canvas = await html2canvas(clone, {
                scale: 2
            });
            document.body.removeChild(clone);

            return canvas.toDataURL('image/png');
        }

        document.getElementById('shareInvoiceBtn').addEventListener('click', async function() {
            const dataUrl = await captureInvoice();
            const response = await fetch(dataUrl);
            const blob = await response.blob();
            const file = new File([blob], 'invoice.png', {
                type: 'image/png'
            });

            const shareText = `Halo {{ $order->customer->name }}, berikut invoice pembelian Anda.`;

            if (navigator.canShare && navigator.canShare({
                    files: [file]
                })) {
                navigator.share({
                    files: [file],
                    title: 'Invoice',
                    text: shareText
            } else {
                alert('Browser tidak mendukung share langsung. Gambar akan di-download.');
                const link = document.createElement('a');
                link.href = dataUrl;
                link.download = 'invoice.png';
                link.click();
            }
        });

        document.getElementById('downloadInvoiceBtn').addEventListener('click', async function() {
            const dataUrl = await captureInvoice();
            const link = document.createElement('a');
            link.href = dataUrl;
            link.download = 'invoice-{{ $order->order_number }}.png';
            link.click();
        });
    </script>
@endpush
