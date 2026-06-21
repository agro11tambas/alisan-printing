<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="keyword" content="">
    <meta name="author" content="WRAPCODERS">

    <title>Alisan</title>

    <link rel="shortcut icon" type="image/x-icon" href="#">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/vendors.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/dataTables.bs5.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/tagify.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/tagify-data.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/select2-theme.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/jquery.steps.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/quill.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/vendors/css/datepicker.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/theme.min.css') }}">

    <style>
        div.dataTables_wrapper .row:first-child {
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

</head>

<body>
    <div class="main-content container-lg">
        <div class="row">
            <div class="col-lg-12">
                <div class="card invoice-container" id="invoiceContent">
                    <div class="card-body p-0">
                        <div class="px-4 pt-4">
                            <div class="d-sm-flex justify-content-between">
                                <div>
                                    <div class="fs-24 fw-bolder font-montserrat-alt text-uppercase text-primary">Alisan
                                    </div>
                                    <address class="text-muted">
                                        Jl. abcde asdkasdas
                                    </address>
                                </div>
                                <div class="lh-lg pt-3 pt-sm-0">
                                    <h2 class="fs-4 fw-bold text-primary">Invoice</h2>
                                    <div>
                                        <span class="fw-bold text-dark">Invoice:</span>
                                        <span class="fw-bold text-primary">{{ $order->order_number }}</span>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark">Date:</span>
                                        <span
                                            class="text-muted">{{ date('d M Y', strtotime($order->created_at)) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="border-dashed">
                        <div class="px-4 py-sm-4">
                            <div class="d-sm-flex gap-4 justify-content-center">
                                <div class="text-sm-end">
                                    <h2 class="fs-16 fw-bold text-dark mb-3">Invoiced To:</h2>
                                    <address class="text-muted lh-lg">
                                        {{ $order->customer->name }}<br>
                                        {{ $order->customer->phone }}<br>
                                        {{ $order->shipping_address }}
                                    </address>
                                </div>
                                <div class="border-end border-end-dashed border-gray-500 d-none d-sm-block"></div>
                                <div class="mt-4 mt-sm-0">
                                    <h2 class="fs-16 fw-bold text-dark mb-3">Payment Details:</h2>
                                    <div class="text-muted lh-lg">
                                        <div>
                                            <span class="text-muted">Total Due:</span>
                                            <span class="fw-bold text-dark">Rp.
                                                {{ number_format($order->grand_total, 0, ',', '.') }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted">Payout Status:</span>
                                            <span class="fw-bold text-success">{{ $order->payment_status }}</span>
                                        </div>
                                        <div>
                                            <span class="text-muted">Payment Method:</span>
                                            <span class="fw-bold text-dark">{{ $order->payment_method }}</span>
                                        </div>
                                    </div>
                                </div>
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
                                    @foreach ($order->orderItems as $item)
                                        <tr>
                                            <td>{{ $item->product->name }}</td>
                                            <td>Rp. {{ number_format($item->product->price, 0, ',', '.') }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td class="text-end">Rp. {{ number_format($item->subtotal, 0, ',', '.') }}
                                            </td>
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
                        <div class="px-4 pt-4 d-sm-flex align-items-center justify-content-between">
                            <div class="d-flex mb-5 mb-sm-0">

                            </div>
                            <div class="text-end">
                                <h6 class="fs-13 fw-bold mt-2">Owner</h6>
                                <p class="fs-11 fw-semibold text-muted">
                                    {{ date('d M Y, H:i', strtotime($order->created_at)) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script>
        window.onload = function() {
            html2canvas(document.getElementById('invoiceContent')).then(function(canvas) {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = '{{ $order->order_number }}.png';
                link.click();

                window.close();
            });
        };
    </script>
</body>

</html>
