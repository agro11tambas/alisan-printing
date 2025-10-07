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
                <a href="/erp/sales/sale-list" class="btn btn-light-brand">
                    <i class="feather-arrow-left me-2"></i>
                    <span>Back</span>
                </a>
                <button type="submit" class="btn btn-primary" form="orderForm">
                    <i class="feather-plus me-2"></i>
                    <span>Add Sale Return</span>
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
            <form action="/erp/sales/sale-returns/store" method="POST" id="orderForm">
                @csrf
                @method('POST')
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
                                            <input type="text" class="form-control" id="order_number" name="order_number" value="{{ old('order_number', $order->order_number) }}" readonly>
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
                                                value="{{ old('return_date', isset($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : date('Y-m-d')) }}">
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
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="customer_id" name="customer_id">
                                                <option disabled selected hidden>Choose Customer</option>
                                                @foreach ($customers as $customer)
                                                @php
                                                $bg = $bgColors[$loop->index % count($bgColors)];
                                                @endphp
                                                <option value="{{ $customer->id }}" data-bg="{{ $bg }}" {{ $order->customer_id == $customer->id ? 'selected' : '' }}>
                                                    {{ $customer->name }}
                                                </option>
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
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="addresses" name="address_id">
                                                <option disabled hidden>Pilih alamat</option>
                                                @if($order->customer)
                                                @foreach($order->customer->addresses as $index => $address)
                                                <option value="{{ $address->id }}" data-map="{{ $address->google_maps }}"
                                                    {{ $order->address_id == $address->id ? 'selected' : '' }}>
                                                    Alamat ke-{{ $index + 1 }} - {{ $address->address }}
                                                </option>
                                                @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        <div id="google-maps-link" class="mt-2">
                                            @if($order->address)
                                            @if($order->address->google_maps)
                                            <a href="{{ $order->address->google_maps }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                                Lihat di Google Maps
                                            </a>
                                            @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3 align-items-center">
                                    <div class="col-lg-2">
                                        <label for="transaction_type" class="fw-semibold">Sale:</label>
                                    </div>
                                    <div class="col-lg-10 mb-0">
                                        <div class="input-group">
                                            <select class="form-select form-control max-select" data-select2-selector="tag" id="transaction_type" name="transaction_type">
                                                <option value="13" data-bg="bg-success">Sale Return</option>
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
                            <div class="col-lg-12">
                                <div class="mb-4">
                                    <h5 class="fw-bold">Add Products:</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered overflow-hidden" id="tab_logic">
                                        <thead>
                                            <tr class="single-item">
                                                <th class="text-center wd-50">#</th>
                                                <th class="text-center wd-450">Product</th>
                                                <th class="text-center wd-150">Qty</th>
                                                <th class="text-center wd-150">Price</th>
                                                <th class="text-center wd-150">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tab_logic_body">
                                            @forelse ($remainingItems ?? [] as $index => $item)


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
                                                    <input type="number" name="qty[]" class="qty form-control" min="0" max="{{ $item->remaining_qty }}" value="0">
                                                    <small class="text-muted">Sisa max: {{ $item->remaining_qty }}</small>
                                                </td>
                                                <td>
                                                    <input type="text" class="price_display form-control" readonly>
                                                    <input type="hidden" name="price[]" class="price">
                                                </td>
                                                <td>
                                                    <input type="text" class="total_display form-control" readonly>
                                                    <input type="hidden" name="total[]" class="total">
                                                </td>
                                            </tr>

                                            @empty
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <!-- <div class="d-flex justify-content-end gap-2 mt-3">
                                    <button type="button" id="delete_row" class="btn btn-md bg-soft-danger text-danger">Delete</button>
                                    <button type="button" id="add_row" class="btn btn-md btn-primary">Add Items</button>
                                </div> -->
                            </div>
                            <div class="col-lg-12 mt-4">
                                <div class="row justify-content-end">
                                    <div class="col-lg-4">
                                        <div class="mb-4">
                                            <h5 class="fw-bold">Grand Total:</h5>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" id="tab_logic_total">
                                                <tbody>
                                                    <tr class="single-item">
                                                        <!-- <th class="fs-10 text-dark text-uppercase">Sub Total</th> -->
                                                        <td class="w-25"><input type="hidden" name="sub_total" placeholder="0.00" class="form-control border-0 bg-transparent p-0" id="sub_total" readonly=""></td>
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
                                                        <td class="bg-gray-100 w-25">
                                                            <input type="text" id="total_amount_display" class="form-control border-0 bg-transparent p-0 fw-700 text-dark" readonly>
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
    // === formatter tampilan ===
    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(num);
    }

    // === hitung per baris ===
    function updateRowTotal(row) {
        const qty = parseFloat(row.find('.qty').val()) || 0;
        const price = parseFloat(row.find('.price').val()) || 0; // HIDDEN (raw)
        const total = qty * price;

        // simpan raw (pakai titik . sebagai decimal)
        row.find('.price').val(price.toFixed(2));
        row.find('.total').val(total.toFixed(2));

        // tampilan (pakai format Indonesia -> 1.234,00)
        row.find('.price_display').val(formatNumber(price));
        row.find('.total_display').val(formatNumber(total));

        calcTotal();
    }

    // === hitung ringkasan ===
    function calcTotal() {
        let sub = 0;
        $('.total').each(function() {
            sub += parseFloat($(this).val()) || 0;
        });

        // raw (hidden)
        $('#sub_total').val(sub.toFixed(2));
        $('#total_amount').val(sub.toFixed(2));

        // tampilan
        if ($('#sub_total_display').length) {
            $('#sub_total_display').val(formatNumber(sub));
        }
        $('#total_amount_display').val(formatNumber(sub));
    }

    $(document).ready(function() {
        // Select2
        $('.select-product').select2({
            placeholder: 'Pilih produk',
            width: '100%'
        });

        // Prefill harga dari option TERPILIH (biar gak 0 saat load)
        $('#tab_logic_body tr').each(function() {
            const row = $(this);
            const sel = row.find('select[name="product_id[]"]');
            const price = parseFloat(sel.find('option:selected').data('price')) || 0;

            row.find('.price').val(price.toFixed(2));
            row.find('.price_display').val(formatNumber(price));
            updateRowTotal(row);
        });

        // Inisialisasi data alamat berdasarkan customer yang sudah dipilih
        const initialCustomerId = $('#customers').val();
        if (initialCustomerId) {
            updateAddresses(initialCustomerId);
        }

        $('#customers').on('change', function() {
            const customerId = $(this).val();
            updateAddresses(customerId);
        });

        $('#addresses').on('change', function() {
            updateGoogleMapsLink();
        });

        function updateAddresses(customerId) {
            const addresses = customerAddresses[customerId] || [];
            const $addressSelect = $('#addresses');
            const selectedAddressId = "{{ $order->address_id ?? '' }}";

            $addressSelect.empty().append('<option disabled hidden>Pilih alamat</option>');

            addresses.forEach(function(address, index) {
                const isSelected = address.id == selectedAddressId;
                $addressSelect.append(
                    `<option value="${address.id}" data-map="${address.google_maps}" ${isSelected ? 'selected' : ''}>
                        Alamat ke-${index + 1} - ${address.address}
                    </option>`
                );
            });

            updateGoogleMapsLink();
        }

        function updateGoogleMapsLink() {
            const selectedOption = $('#addresses').find('option:selected');
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
        }
    });

    // === event: ganti produk → set harga dari option ===
    $(document).on('change', 'select[name="product_id[]"]', function() {
        const row = $(this).closest('tr');
        const price = parseFloat($(this).find('option:selected').data('price')) || 0;

        row.find('.price').val(price.toFixed(2)); // raw
        row.find('.price_display').val(formatNumber(price)); // display
        updateRowTotal(row);
    });

    // === event: ubah qty ===
    $(document).on('input', '.qty', function() {
        const row = $(this).closest('tr');
        const max = parseFloat($(this).attr('max')) || Infinity;
        let qty = parseFloat($(this).val()) || 0;
        if (qty > max) {
            qty = max;
            $(this).val(max);
        }
        updateRowTotal(row);
    });

    // === (opsional) kalau hidden price diganti manual ===
    $(document).on('input', '.price', function() {
        updateRowTotal($(this).closest('tr'));
    });
</script>
@endpush