@extends('erp.layouts.main')

@section('breadcrumb')
<div class="page-header sticky-top">
    <div class="page-header-left d-flex align-items-center">
        <div class="page-header-title">
            <h5 class="m-b-10">Sale Returns</h5>
        </div>
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
            <li class="breadcrumb-item">Sale Returns</li>
            <li class="breadcrumb-item">Create Sale Returns</li>
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
                <a href="/erp/sales/sale-returns" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="orderForm">
                    <i class="feather-plus me-2"></i>
                    <span>Edit Sale Return</span>
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
            <form action="/erp/sales/sale-returns/update/{{ $saleReturn->id }}" method="POST" id="orderForm">
                @csrf
                @method('PUT')
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="row">
                            <input type="hidden" name="sale_order_id" value="{{ $order->id }}">
                            <input type="hidden" name="customer_id" value="{{ $order->customer_id }}">
                            <div class="col-lg-12">
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="order_number" class="fw-semibold">Order Number:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="order_number" name="order_number"
                                                value="{{ old('order_number', $saleReturn->order_number) }}" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="return_date" class="fw-semibold">Sale Return Date:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="return_date" name="return_date"
                                                value="{{ old('return_date', \Carbon\Carbon::parse($saleReturn->return_date)->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                </div>
                                <!-- Customer & Address -->
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="customer_id" class="fw-semibold">Customer:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <select class="form-select form-control max-select" id="customer_id" name="customer_id">
                                            @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ $saleReturn->customer_id == $customer->id ? 'selected' : '' }}>
                                                {{ $customer->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="addresses" class="fw-semibold">Address:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <select class="form-select form-control max-select" id="addresses" name="address_id">
                                            @if($order->customer)
                                            @foreach($order->customer->addresses as $index => $address)
                                            <option value="{{ $address->id }}" data-map="{{ $address->google_maps }}"
                                                {{ $saleReturn->return_address_id == $address->id ? 'selected' : '' }}>
                                                Alamat ke-{{ $index + 1 }} - {{ $address->address }}
                                            </option>
                                            @endforeach
                                            @endif
                                        </select>
                                        <div id="google-maps-link" class="mt-2">
                                            @if($saleReturn->google_map)
                                            <a href="{{ $saleReturn->google_map }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                Lihat di Google Maps
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="edit_note" class="fw-semibold">Edit Note:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <textarea class="form-control" id="edit_note" name="edit_note" rows="2" placeholder="Tambahkan catatan edit..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product Table -->
                <div class="card stretch stretch-full">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="fw-bold mb-4">Add Products:</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered overflow-hidden" id="tab_logic">
                                        <thead>
                                            <tr>
                                                <th class="text-center wd-50">#</th>
                                                <th class="text-center wd-450">Product</th>
                                                <th class="text-center wd-150">Qty</th>
                                                <th class="text-center wd-150">Price</th>
                                                <th class="text-center wd-150">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($remainingItems as $index => $item)
                                            <tr id="addr{{ $index }}">
                                                <td>{{ $index + 1 }}</td>
                                                <input type="hidden" name="order_item_ids[]" value="{{ $item->id }}">
                                                <td>
                                                    <select class="form-control select-product" name="product_id[]">
                                                        <option value="" disabled hidden>Pilih produk</option>
                                                        @foreach($products as $product)
                                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}"
                                                            {{ $product->id == $item->product_id ? 'selected' : '' }}>
                                                            [{{ $product->sku }}] {{ $product->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="qty[]" class="qty form-control"
                                                        min="0" max="{{ $item->remaining_qty }}"
                                                        value="{{ old('qty.'.$index, $item->return_qty ?? 0) }}">
                                                    <!-- <small class="text-muted">Sisa max: {{ $item->remaining_qty }}</small> -->
                                                </td>
                                                <td>
                                                    <input type="text" class="price_display form-control" readonly>
                                                    <input type="hidden" name="price[]" class="price"
                                                        value="{{ old('price.'.$index, $item->return_price ?? 0) }}">
                                                </td>
                                                <td>
                                                    <input type="text" class="total_display form-control" readonly>
                                                    <input type="hidden" name="total[]" class="total"
                                                        value="{{ old('total.'.$index, ($item->return_qty ?? 0) * ($item->return_price ?? 0)) }}">
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>

                                    </table>
                                </div>

                                <!-- <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button type="button" id="delete_row" class="btn btn-md bg-soft-danger text-danger">Delete</button>
                                    <button type="button" id="add_row" class="btn btn-md btn-primary">Add Items</button>
                                </div> -->
                            </div>

                            <!-- Grand Total -->
                            <div class="col-lg-12 mt-4">
                                <div class="row justify-content-end">
                                    <div class="col-lg-4">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <td><input type="hidden" name="sub_total" id="sub_total" class="form-control" readonly></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-gray-100">Grand Total</th>
                                                    <td class="bg-gray-100">
                                                        <input type="text" id="total_amount_display" class="form-control fw-700 text-dark" readonly>
                                                        <input type="hidden" name="total_amount" id="total_amount">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
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
    $(document).ready(function() {
        let i = parseInt('{{ count($saleReturn->items) }}');

        function initSelect2() {
            $('.select-product').select2({
                width: '100%',
                placeholder: 'Pilih produk'
            });
        }

        initSelect2();

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(num);
        }

        function updateRowTotal(row) {
            let qty = parseFloat(row.find(".qty").val()) || 0;
            let price = parseFloat(row.find(".price").val()) || 0;
            let total = qty * price;

            // simpan raw (hidden)
            row.find(".price").val(price.toFixed(2));
            row.find(".total").val(total.toFixed(2));

            // tampilkan formatted
            row.find(".price_display").val(formatNumber(price));
            row.find(".total_display").val(formatNumber(total));

            calc_total();
        }

        function calc_total() {
            let total = 0;
            $(".total").each(function() {
                total += parseFloat($(this).val()) || 0;
            });

            // raw hidden
            $("#sub_total").val(total.toFixed(2));
            $("#total_amount").val(total.toFixed(2));

            // display formatted
            $("#sub_total_display").val(formatNumber(total));
            $("#total_amount_display").val(formatNumber(total));
        }

        $(document).ready(function() {
            $('.select-product').select2({
                width: '100%',
                placeholder: 'Pilih produk'
            });

            // Prefill row saat load edit
            $("#tab_logic tbody tr").each(function() {
                updateRowTotal($(this));
            });

            // Qty berubah
            $(document).on('input', '.qty', function() {
                let row = $(this).closest('tr');
                let max = parseFloat($(this).attr('max')) || Infinity;
                let qty = parseFloat($(this).val()) || 0;
                if (qty > max) {
                    $(this).val(max);
                    qty = max;
                }
                updateRowTotal(row);
            });

            // Product dipilih → update price
            $(document).on('change', '.select-product', function() {
                let row = $(this).closest('tr');
                let price = parseFloat($(this).find('option:selected').data('price')) || 0;
                row.find('.price').val(price.toFixed(2));
                row.find('.price_display').val(formatNumber(price));
                updateRowTotal(row);
            });

            // Price diubah manual
            $(document).on('input', '.price', function() {
                updateRowTotal($(this).closest('tr'));
            });

            calc_total();
        });

        // Google Maps Link Update
        function updateGoogleMapsLink() {
            const selectedOption = $('#addresses').find('option:selected');
            const mapUrl = selectedOption.data('map');
            if (mapUrl) {
                $('#google-maps-link').html(`<a href="${mapUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">Lihat di Google Maps</a>`);
            } else {
                $('#google-maps-link').empty();
            }
        }

        $('#customer_id').on('change', function() {
            const customerId = $(this).val();
            const addresses = customerAddresses[customerId] || [];
            const $addressSelect = $('#addresses');
            $addressSelect.empty().append('<option disabled hidden>Pilih alamat</option>');
            addresses.forEach(function(address, index) {
                $addressSelect.append(`<option value="${address.id}" data-map="${address.google_maps}">Alamat ke-${index+1} - ${address.address}</option>`);
            });
            updateGoogleMapsLink();
        });

        $('#addresses').on('change', updateGoogleMapsLink);
    });

    function showError(element, message) {
        // Hapus pesan error lama
        $(element).next(".invalid-feedback").remove();

        // Tambah pesan error baru
        $(element).after(`<div class="invalid-feedback">${message}</div>`);

        // Tambahkan kelas is-invalid
        $(element).addClass("is-invalid");
    }

    // Hapus error kalau user pilih sesuatu
    $("#edit_note").on("change", function() {
        $(this).removeClass("is-invalid");
        $(this).next(".invalid-feedback").remove();
    });


    $("form").on("submit", function(e) {
        let valid = true;

        if (!$("#edit_note").val()) {
            showError($("#edit_note"), "Catatan edit wajib diisi");
            valid = false;
        }

        if (!valid) {
            e.preventDefault(); // stop submit
        }
    });
</script>
@endpush