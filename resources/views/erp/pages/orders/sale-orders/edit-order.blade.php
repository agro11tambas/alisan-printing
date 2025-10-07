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
            <li class="breadcrumb-item">Edit Order</li>
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
                    <span>Edit Order</span>
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
            <form action="/erp/orders/sale-orders/update/{{ $order->id }}" method="POST" id="orderForm">
                @csrf
                @method('PUT')
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
                                            <input type="date" class="form-control" id="order_date" name="order_date" value="{{ old('order_date', isset($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : date('Y-m-d')) }}">
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
                                                <option value="{{ $customer->id }}" data-bg="{{ $bg }}" {{ $customer->id == $order->customer_id ? 'selected' : '' }}>{{ $customer->name }}</option>
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
                                            @forelse ($order->orderItems ?? [0] as $index => $item)
                                            <tr id="addr{{ $index }}">
                                                <td>{{ $index + 1 }}</td>
                                                <input type="hidden" name="order_item_ids[]" value="{{ $item->id }}">
                                                <td>
                                                    <select class="form-control select-product" data-select2-selector="status" name="product[]" id="product_{{ $index }}">
                                                        <option value="" disabled {{ !isset($item->product_id) ? 'selected hidden' : '' }}>Pilih produk</option>
                                                        @foreach ($products as $product)
                                                        <option value="{{ $product->id }}"
                                                            data-price="{{ $product->price }}"
                                                            {{ isset($item->product_id) && $product->id == $item->product_id ? 'selected' : '' }}>
                                                            {{ $product->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="number" name="qty[]" class="form-control qty" id="qty_{{ $index }}" placeholder="Qty" min="1" value="{{ $item->quantity ?? '' }}"></td>
                                                <td><input type="number" name="price[]" class="form-control price" id="price_{{ $index }}" readonly value="{{ $item->price ?? '' }}"></td>
                                                <td><input type="number" name="total[]" class="form-control total" id="total_{{ $index }}" readonly value="{{ $item->total ?? ($item->quantity * $item->price) }}"></td>
                                            </tr>
                                            @empty
                                            <tr id="addr0">
                                                <td>1</td>
                                                <input type="hidden" name="order_item_ids[]" value="">
                                                <td>
                                                    <select class="form-control select-product" data-select2-selector="status" name="product[]" id="product_0">
                                                        <option value="" disabled selected hidden>Pilih produk</option>
                                                        @foreach ($products as $product)
                                                        <option value="{{ $product->id }}" data-price="{{ $product->price }}">{{ $product->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="number" name="qty[]" class="form-control qty" id="qty_0" placeholder="Qty" min="1"></td>
                                                <td><input type="number" name="price[]" class="form-control price" id="price_0" readonly></td>
                                                <td><input type="number" name="total[]" class="form-control total" id="total_0" readonly></td>
                                            </tr>
                                            @endforelse
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
                                                <th class="fs-10 text-dark text-uppercase">Tax</th>
                                                <td class="w-25">
                                                    <div class="input-group mb-2 mb-sm-0">
                                                        <input type="number" class="form-control border-0 bg-transparent p-0" id="tax" placeholder="0">
                                                        <div class="input-group-addon">%</div>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="single-item">
                                                <th class="fs-10 text-dark text-uppercase">Tax Amount</th>
                                                <td class="w-25"><input type="number" name="tax_amount" id="tax_amount" placeholder="0.00" class="form-control border-0 bg-transparent p-0" readonly=""></td>
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
        let i = parseInt(`{{ count($order->orderItems ?? []) }}`);

        function initSelect2() {
            $('[data-select2-selector="status"]').select2({
                placeholder: 'Pilih produk',
                width: '100%'
            });
        }

        initSelect2(); // awal

        $('#tab_logic').find('tr').each(function() {
            let qty = parseFloat($(this).find('.qty').val()) || 0;
            let price = parseFloat($(this).find('.price').val()) || 0;
            $(this).find('.total').val((qty * price).toFixed(2));
        });
        calc_total();

        $('#add_row').click(function() {
            let $lastRow = $('#addr' + (i - 1));
            let $newRow = $lastRow.clone();

            $newRow.attr('id', 'addr' + i);
            $newRow.find('td:first').text(i + 1);

            // Tambah hidden input baru untuk order_item_ids[]
            let hiddenInput = `<input type="hidden" name="order_item_ids[]" value="">`;
            if ($newRow.find('input[name="order_item_ids[]"]').length) {
                $newRow.find('input[name="order_item_ids[]"]').val(''); // kosongkan jika ada
            } else {
                $newRow.prepend(hiddenInput); // tambahkan jika tidak ada
            }

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

    $(document).ready(function() {
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
</script>
@endpush