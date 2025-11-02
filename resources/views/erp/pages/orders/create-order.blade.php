@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Order</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Order</li>
            <li class="breadcrumb-item">Create Order</li>
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
                <a href="/erp/orders/sale-orders" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="orderForm">
                    <i class="feather-plus me-2"></i>
                    <span>Add Order</span>
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
@if(session('error'))
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
            <form action="/erp/orders/store" method="POST" id="orderForm">
                @csrf
                @method('POST')
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="order_date" class="fw-semibold">Order Date:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="order_date" name="order_date" value="{{ date('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>
                                <!--  -->
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="customers" class="fw-semibold">Customer:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            @php
                                            $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                            @endphp
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="customers" name="customers[]">
                                                <option disabled selected hidden>Choose Customer</option>
                                                @foreach ($customers as $index => $customer)
                                                @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $customer->id }}" data-bg="{{ $bg }}">{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="addresses" class="fw-semibold">Address:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="addresses" name="addresses[]">
                                                <option disabled selected hidden>Pilih alamat</option>
                                            </select>
                                        </div>
                                        <div id="google-maps-link" class="mt-2"></div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="transaction_type" class="fw-semibold">Sale:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            @php
                                            $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                            @endphp
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="transaction_type" name="transaction_type">
                                                <option disabled selected hidden>Choose Transaction Type</option>
                                                @foreach ($transactionTypes as $index => $transactionType)
                                                @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $transactionType->id }}" data-bg="{{ $bg }}">{{ $transactionType->type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="cash_bank_account_id" class="fw-semibold">Cash/Bank Account:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            @php
                                            $bgColors = ['bg-danger', 'bg-warning', 'bg-primary', 'bg-indigo', 'bg-success'];
                                            @endphp
                                            <select class="form-select form-control max-select" data-select2-selector="tag" name="cash_bank_account_id" id="cash_bank_account_id">
                                                <option value="" disabled selected hidden>Pilih Bank atau Cash Account</option>
                                                @foreach ($cashAccounts as $cash)
                                                @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $cash->id }}" data-bg="{{ $bg }}">Cash - {{ $cash->type }}</option>
                                                @endforeach
                                                @foreach ($bankAccounts as $bank)
                                                @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $bank->id }}" data-bg="{{ $bg }}">Bank - {{ $bank->type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="transaction_date" value="{{ date('Y-m-d') }}">
                                <input type="hidden" name="note" value="Auto Note">
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="payment_status" class="fw-semibold">Payment Status:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="payment_status" name="payment_status">
                                                <option disabled selected hidden>Choose Payment Status</option>
                                                <option value="Paid" data-bg="bg-success">Paid</option>
                                                <option value="Partially Paid" data-bg="bg-warning">Partially Paid</option>
                                                <option value="Unpaid" data-bg="bg-danger">Unpaid</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="paid_amount" class="fw-semibold">Paid Amount:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="paid_amount" name="paid_amount" value="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="status" class="fw-semibold">Status:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="status" name="status">
                                                <option disabled selected hidden>Choose Status</option>
                                                <option value="Draft" data-bg="bg-warning">Draft</option>
                                                <option value="Sale List" data-bg="bg-dark">Sale List</option>
                                                <option value="Waiting List" data-bg="bg-primary">Waiting List</option>
                                                <option value="Complete List" data-bg="bg-danger">Complete List</option>
                                                <option value="Delivered" data-bg="bg-info">Delivered</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-4">
                                    <h5 class="fw-bold">Add Products:</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered overflow-hidden" id="tab_logic">
                                        <thead>
                                            <tr class="single-item">
                                                <th class="text-center">#</th>
                                                <th class="text-center wd-450">Product</th>
                                                <th class="text-center wd-150">Qty</th>
                                                <th class="text-center wd-150">Price</th>
                                                <th class="text-center wd-150">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tab_logic">
                                            <tr id="addr0">
                                                <td>1</td>
                                                <td>
                                                    <select class="form-control select-product" data-select2-selector="status" name="product[]" id="product_0">
                                                        <option value="" disabled selected hidden>Pilih produk</option>
                                                        @foreach ($products as $index => $product)
                                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="number" name="qty[]" class="form-control qty" id="qty_0" placeholder="Qty" min="1"></td>
                                                <td><input type="number" name="price[]" class="form-control price" id="price_0" readonly></td>
                                                <td><input type="number" name="total[]" class="form-control total" id="total_0" readonly></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button type="button" id="delete_row" class="btn btn-md bg-soft-danger text-danger">Delete</button>
                                    <button type="button" id="add_row" class="btn btn-md btn-primary">Add Items</button>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="mb-4">
                                    <h5 class="fw-bold">Grand Total:</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="tab_logic_total">
                                        <tbody>
                                            <tr class="single-item">
                                                <th class="fs-10 text-dark text-uppercase">Sub Total</th>
                                                <td class="w-25"><input type="number" name="sub_total" placeholder="0.00" class="form-control border-0 bg-transparent p-0" id="sub_total" readonly=""></td>
                                            </tr>
                                            <!-- <tr class="single-item">
                                                <th class="fs-10 text-dark text-uppercase">Discount</th>
                                                <td class="w-25">
                                                    <input type="text" readonly class="form-control border-0 bg-transparent p-0" value="{{ $discount['type'] === 'Percentage' ? $discount['amount'].'%' : 'Rp'.number_format($discount['amount'],0,',','.') }}">
                                                    <input type="hidden" id="discount_type" value="{{ $discount['type'] }}">
                                                    <input type="hidden" id="discount_amount_value" value="{{ $discount['amount'] }}">
                                                    <input type="hidden" id="discount_condition" value="{{ $discount['condition_type'] }}">
                                                    <input type="hidden" id="discount_minimum" value="{{ $discount['minimum_requirement'] }}">
                                                </td>
                                            </tr> -->
                                            <tr class="single-item">
                                                <th class="fs-10 text-dark text-uppercase bg-gray-100">Grand Total</th>
                                                <td class="bg-gray-100 w-25"><input type="number" name="total_amount" id="total_amount" placeholder="0.00" class="form-control border-0 bg-transparent p-0 fw-700 text-dark" readonly=""></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- <div class="col-lg-12">
        <div class="card stretch stretch-full">
            <div class="card-body p-0">
                <div class="table-responsive">
                </div>
            </div>
        </div>
    </div> -->
</div>
@endsection

@push('scripts')
<script>
    const customerAddresses = <?php echo json_encode($customers->mapWithKeys(function ($customer) {
                                    return [$customer->id => $customer->addresses->map(function ($address) {
                                        return [
                                            'id' => $address->id,
                                            'address' => $address->address,
                                            'google_maps' => $address->google_maps,
                                        ];
                                    })];
                                })); ?>;
</script>
<script>
    // Saat produk dipilih
    $(document).on('change', 'select[name="product[]"]', function() {
        let selectedOption = $(this).find('option:selected');
        let price = selectedOption.data('price');

        let row = $(this).closest('tr');
        row.find('input[name="price[]"]').val(price);

        let qty = parseFloat(row.find('input[name="qty[]"]').val()) || 0;
        row.find('input[name="total[]"]').val((price * qty).toFixed(2));

        calc_total(); // update total keseluruhan
    });


    // Saat qty berubah
    $(document).on('input', 'input[name="qty[]"]', function() {
        let row = $(this).closest('tr');
        let qty = parseFloat($(this).val()) || 0;
        let price = parseFloat(row.find('input[name="price[]"]').val()) || 0;
        row.find('input[name="total[]"]').val((qty * price).toFixed(2));
        calc_total();
    });

    $(document).ready(function() {
        let i = 1;

        function initSelect2() {
            $('[data-select2-selector="status"]').select2({
                placeholder: 'Pilih produk',
                width: '100%'
            });
        }

        initSelect2(); // awal

        $('#add_row').click(function() {
            let $lastRow = $('#addr' + (i - 1));
            let $newRow = $lastRow.clone();

            $newRow.attr('id', 'addr' + i);
            $newRow.find('td:first').text(i + 1);

            $newRow.find('select[name="product[]"]')
                .attr('id', 'product_' + i)
                .val('')
                .removeClass('select2-hidden-accessible') // penting!
                .next('.select2') // hapus UI select2 lama
                .remove();

            $newRow.find('input[name="qty[]"]').attr('id', 'qty_' + i).val('');
            $newRow.find('input[name="price[]"]').attr('id', 'price_' + i).val('');
            $newRow.find('input[name="total[]"]').attr('id', 'total_' + i).val('');

            $('#tab_logic').append($newRow);
            initSelect2(); // re-inisialisasi Select2
            i++;
        });

        $('#delete_row').click(function() {
            if (i > 1) {
                $('#addr' + (i - 1)).remove();
                i--;
            }
            calc();
        });

        $('#tab_logic').on('keyup change', 'input, select', function() {
            calc();
        });

        $('#tax').on('keyup change', function() {
            calc_total();
        });
    });

    function calc() {
        $("#tab_logic tbody tr").each(function(i, element) {
            var html = $(this).html();
            if (html != "") {
                var qty = $(this).find(".qty").val();
                var price = $(this).find(".price").val();
                $(this)
                    .find(".total")
                    .val(qty * price);
                calc_total();
            }
        });
    }

    function calc_total() {
        total = 0;
        $(".total").each(function() {
            total += parseInt($(this).val());
        });
        $("#sub_total").val(total.toFixed(2));
        // tax_sum = (total / 100) * $("#tax").val();
        // $("#tax_amount").val(tax_sum.toFixed(2));
        $("#total_amount").val((total).toFixed(2));
    }

    document.getElementById('orderForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;

        // Reset semua error
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        let isValid = true;

        // Validasi produk per baris
        const rows = form.querySelectorAll('#tab_logic tbody tr');
        rows.forEach(row => {
            const product = row.querySelector('select[name="product[]"]');
            const qty = row.querySelector('input[name="qty[]"]');

            if (!product || !product.value) {
                isValid = false;
                product.classList.add('is-invalid');
                showError(product, 'Produk wajib dipilih');
            }

            if (!qty || qty.value.trim() === '' || parseInt(qty.value) < 1) {
                isValid = false;
                qty.classList.add('is-invalid');
                showError(qty, 'Qty minimal 1');
            }
        });

        // Validasi field lainnya
        const fields = [{
                selector: 'input[name="order_date"]',
                message: 'Tanggal order wajib diisi'
            },
            {
                selector: 'select[name="customers[]"]',
                message: 'Customer wajib dipilih'
            },
            {
                selector: 'select[name="addresses[]"]',
                message: 'Alamat wajib dipilih'
            },
            // {
            //     selector: 'select[name="payment_method"]',
            //     message: 'Metode pembayaran wajib dipilih'
            // },
            {
                selector: 'select[name="payment_status"]',
                message: 'Status pembayaran wajib dipilih'
            },
            {
                selector: 'select[name="status"]',
                message: 'Status order wajib dipilih'
            },
        ];

        fields.forEach(field => {
            const el = form.querySelector(field.selector);
            const value = el?.value?.trim();
            if (!value || value === '' || value === 'null') {
                isValid = false;
                el.classList.add('is-invalid');
                showError(el, field.message);
            }
        });

        // Jika semua valid, submit form
        if (isValid) {
            form.submit();
        }
    });

    $(document).ready(function() {
        $('#customers').on('change', function() {
            const customerId = $(this).val();
            const addresses = customerAddresses[customerId] || [];

            $('#addresses').empty().append(' ');

            addresses.forEach(function(address, index) {
                $('#addresses').append(
                    `<option value="${address.id}" data-map="${address.google_maps}">Alamat ke-${index + 1} - ${address.address}</option>`
                );
            });
        });
        $('#addresses').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const mapUrl = selectedOption.data('map');

            if (mapUrl) {
                $('#google-maps-link').html(`
                <a href="${mapUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                    Lihat di Google Maps
                </a>
            `);
            } else {
                $('#google-maps-link').empty();
            }
        });
    });

    function showError(el, message) {
        // Tangani select2: tidak ubah styling, hanya tampilkan pesan
        if ($(el).hasClass('select2-hidden-accessible')) {
            const select2Container = $(el).next('.select2');

            // Cari apakah sudah ada feedback
            if (select2Container.next('.invalid-feedback').length === 0) {
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                feedback.style.display = 'block';
                select2Container[0].after(feedback);
            }

            return;
        }

        // Input biasa
        el.classList.add('is-invalid');
        const container = el.closest('.input-group') || el.parentNode;
        if (!container.querySelector('.invalid-feedback')) {
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = message;
            feedback.style.display = 'block';
            container.appendChild(feedback);
        }
    }
</script>
@endpush
