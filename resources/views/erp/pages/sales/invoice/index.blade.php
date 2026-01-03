@extends('erp.layouts.main')

@push('styles')
    <style>
        /* Wrapper untuk scroll horizontal di mobile */
        .invoice-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            width: 100%;
        }

        /* Force desktop layout untuk invoice */
        #invoiceContent {
            min-width: 800px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .table-middle {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table-middle th,
        .table-middle td {
            padding: 7px 32px !important;
            vertical-align: middle;
            border: 1px solid #dee2e6;
        }

        .table-middle thead th {
            font-weight: 600;
            white-space: nowrap;
        }

        .table-middle tbody tr:hover {
            background-color: #f1f3f5;
        }

        /* Allow product name to wrap */
        .table-middle td:first-child {
            white-space: normal !important;
            word-break: break-word !important;
        }

        /* Media query untuk mobile */
        @media (max-width: 768px) {

            /* Buttons tetap responsive */
            .card.bg-transparent .card-body {
                flex-direction: column !important;
            }

            .card.bg-transparent .card-body .btn {
                width: 100% !important;
            }
        }
    </style>
@endpush

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
                <!-- Wrapper untuk horizontal scroll di mobile -->
                <div class="invoice-wrapper">
                    <div class="card invoice-container" id="invoiceContent">
                        <div class="card-body p-0">
                            <div class="px-4 pt-4 border-bottom mb-3">
                                <div class="row justify-content-between">
                                    <div class="col-lg-4">
                                        @php
                                            $logoPath = $invoice->logo
                                                ? str_replace('public/', '', $invoice->logo)
                                                : null;
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
                                        <div>
                                            <span class="fw-bold text-dark">Invoice No:</span>
                                            <span class="fw-bold text-primary">{{ $order->order_number }}</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark">Invoice Date:</span>
                                            <span
                                                class="text-muted">{{ date('d M Y', strtotime($order->order_date)) }}</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark">Due Date:</span>
                                            <span class="text-muted">
                                                {{ $order->due_date ? date('d M Y', strtotime($order->due_date)) : '-' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-0">
                                <div class="row gap-4 justify-content-between">
                                    <div class="col-lg-12">
                                        <h2 class="fs-16 fw-bold text-dark mb-3">Invoiced To:</h2>
                                        <address class="text-muted lh-lg">
                                            {{ $order->business_name }}<br>
                                            <!--{{ $order->customer->name }}<br>-->
                                            {{ $order->customer->phone }}<br>
                                            {{ $order->shipping_address }}
                                        </address>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-middle">
                                    <thead>
                                        <tr>
                                            <th class="wd-300">Product</th>
                                            <th class="wd-100">Price</th>
                                            <th class="wd-100">QTY</th>
                                            <th class="text-end wd-100">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $items = $order->orderItems ?? ($return->items ?? collect());
                                        @endphp

                                        @foreach ($items as $item)
                                            <tr>
                                                <td style="white-space: normal; word-break: break-word; max-width: 250px;">
                                                    @if ($item->product)
                                                        {{ $item->product->name }}
                                                    @elseif ($item->productBundle)
                                                        @php
                                                            $productNames = $item->productBundle->products
                                                                ->pluck('name')
                                                                ->toArray();
                                                        @endphp
                                                        {{ implode(' + ', $productNames) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($item->product)
                                                        Rp.
                                                        {{ number_format($item->price, 0, ',', '.') }}
                                                    @endif

                                                    @if ($item->productBundle)
                                                        Rp.
                                                        {{ number_format($item->price, 0, ',', '.') }}
                                                    @endif
                                                </td>
                                                <td>{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                                <td class="text-end">Rp. {{ number_format($item->subtotal, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td colspan="2" class="border-0"></td>
                                            <td class="fw-semibold text-dark text-lg-end border-0 text-end">Sub Total</td>
                                            <td class="text-dark border-0 text-end">Rp.
                                                {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="border-0"></td>
                                            <td class="fw-semibold text-dark text-lg-end border-0 text-end">Discount</td>
                                            <td class="text-danger border-0 text-end">- Rp.
                                                {{ number_format($order->discount, 0, ',', '.') }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="border-0"></td>
                                            <td class="fw-semibold text-dark text-lg-end border-0 text-end">Grand Total</td>
                                            <td class="fw-bold text-primary border-0 text-end">Rp.
                                                {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4">
                                <div class="alert alert-dismissible p-4 alert-soft-primary-message" role="alert">
                                    <h5 class="mb-4">Syarat & Ketentuan</h5>
                                    <p class="mb-0">
                                        @forelse($invoice->termAndConditions as $term)
                                            <i class="feather feather-alert-circle me-2"></i> {{ $term->content }}<br>
                                        @empty
                                            <i class="feather feather-alert-circle me-2"></i> Tidak ada Syarat &
                                            Ketentuan.<br>
                                        @endforelse
                                    </p>
                                </div>
                            </div>
                            <div class="px-4 pt-4 d-sm-flex align-items-center justify-content-between">
                                <div class="mb-5 mb-sm-0">
                                    <h6 class="fs-14 fw-bold">Bank</h6>
                                    <p class="fs-14">BANK: {{ $invoice->bank_name }}<br>
                                        Atas Nama: <strong class="text-primary">{{ $invoice->name }}</strong><br>
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
                </div>
                <!-- End wrapper -->

                <div class="card align-items-center bg-transparent border-0 shadow-none">
                    <div class="card-body d-flex gap-3">
                        <button class="btn btn-success" id="shareInvoiceBtn">
                            <i class="feather-share-2 me-2"></i> Share ke WhatsApp
                        </button>
                        {{-- <button class="btn btn-primary" id="downloadInvoiceBtn">
                            <i class="feather-download me-2"></i> Download PNG
                        </button> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        $(document).ready(function() {
            // Button Share ke WhatsApp
            $('#shareInvoiceBtn').on('click', async function() {
                const button = $(this);
                const originalText = button.html();

                // Disable button dan show loading
                button.prop('disabled', true);
                button.html('<i class="feather-loader me-2"></i> Converting...');

                try {
                    // Convert invoice ke gambar
                    const canvas = await html2canvas(document.querySelector("#invoiceContent"), {
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        backgroundColor: '#ffffff'
                    });

                    // Convert canvas ke blob dengan format JPG
                    const imageData = canvas.toDataURL('image/jpeg', 0.95);

                    // Upload ke server
                    button.html('<i class="feather-loader me-2"></i> Uploading...');

                    const response = await fetch('{{ route('invoice.convert') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            image: imageData,
                            order_id: '{{ $order->id }}'
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Buat pesan WhatsApp
                        const customerPhone = '{{ $order->customer->phone }}';
                        const phoneNumber = customerPhone.replace(/[^0-9]/g, '');
                        const formattedPhone = phoneNumber.startsWith('0') ?
                            '62' + phoneNumber.substring(1) : phoneNumber;

                        const message =
                            `Berikut Invoice *${invoiceNo}*
                            ${result.url}

                            ❗️*Harap MELUNASI Tagihan*❗️
                            Terlebih dahulu sebelum proses produksi dimulai.

                            🧾 Setelah pembayaran diterima, produksi akan berjalan sesuai estimasi yang disepakati.

                            ✨️ *REKENING BCA*
                            Nomor: *0590712647*
                            Nama: *STEFAN LEWIS*

                            📌 *WAJIB:*
                            Mengirim bukti transfer setelah pembayaran`;

                        const whatsappUrl =
                            `https://wa.me/${formattedPhone}?text=${encodeURIComponent(message)}`;

                        // Buka WhatsApp
                        window.open(whatsappUrl, '_blank');

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Invoice berhasil dikonversi. WhatsApp akan terbuka.',
                            timer: 2000
                        });
                    } else {
                        throw new Error(result.message || 'Gagal mengkonversi invoice');
                    }

                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat mengkonversi invoice: ' + error.message
                    });
                } finally {
                    // Reset button
                    button.prop('disabled', false);
                    button.html(originalText);
                }
            });

            // Button Download PNG
            $('#downloadInvoiceBtn').on('click', async function() {
                const button = $(this);
                const originalText = button.html();

                button.prop('disabled', true);
                button.html('<i class="feather-loader me-2"></i> Downloading...');

                try {
                    const canvas = await html2canvas(document.querySelector("#invoiceContent"), {
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        backgroundColor: '#ffffff'
                    });

                    // Download
                    const link = document.createElement('a');
                    link.download = 'Invoice_{{ $order->order_number }}.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Invoice berhasil didownload',
                        timer: 2000
                    });

                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat mendownload invoice'
                    });
                } finally {
                    button.prop('disabled', false);
                    button.html(originalText);
                }
            });
        });
    </script>
@endpush
