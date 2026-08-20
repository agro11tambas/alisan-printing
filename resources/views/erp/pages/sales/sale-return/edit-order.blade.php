@extends('erp.layouts.main')

@push('styles')
    <style>
        /* 🔹 Perbesar font pada select bawaan (kalau belum diinisialisasi Select2) */
        .select-product {
            font-size: 16px !important;
            padding: 8px 10px !important;
            height: 42px !important;
        }

        /* 🔹 Perbesar font di dalam Select2 container */
        .select2-container--bootstrap-5 .select2-selection--single {
            height: 42px !important;
            font-size: 16px !important;
            line-height: 42px !important;
        }

        /* 🔹 Perbesar teks hasil pilihan */
        .select2-selection__rendered {
            font-size: 16px !important;
            line-height: 42px !important;
            padding-left: 10px !important;
        }

        /* 🔹 Perbesar teks di dropdown Select2 */
        .select2-results__option {
            font-size: 16px !important;
            padding: 4px 8px !important;
        }

        /* 🔹 Perbesar ikon dropdown */
        .select2-selection__arrow {
            height: 42px !important;
            right: 10px !important;
        }

        #notes {
            font-size: 16px;
        }

        #notes::placeholder {
            font-size: 16px;
        }

        .product-item {
            border-radius: 12px;
            margin-bottom: 12px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: minmax(380px, 4fr) 130px 130px 130px 130px;
            gap: 10px;
            align-items: start;
        }

        .product-grid-header {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }

        .product-col-span-2 {
            grid-column: span 1;
        }

        .product-grid .form-group {
            display: flex;
            flex-direction: column;
        }

        .product-grid .form-group>label {
            display: none !important;
        }

        .product-grid .form-control,
        .product-grid .select2-container--bootstrap-5 .select2-selection--single {
            height: 44px !important;
        }
    </style>
@endpush

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
    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: "{{ session('error') }}",
            });
        </script>
    @endif
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
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
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="order_number" class="fw-semibold">Order Number:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="order_number"
                                                    name="order_number"
                                                    value="{{ old('order_number', $saleReturn->order_number) }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="return_date" class="fw-semibold">Sale Return Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="datetime-local" class="form-control" id="return_date"
                                                    name="return_date"
                                                    value="{{ old('return_date', \Carbon\Carbon::parse($saleReturn->return_date)->format('Y-m-d\TH:i')) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="customer_id" class="fw-semibold">Customer:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <select class="form-select form-control max-select" id="customer_id"
                                                data-select2-selector="tag" name="customer_id">
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->id }}"
                                                        {{ $saleReturn->customer_id == $customer->id ? 'selected' : '' }}>
                                                        {{ $customer->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="customer_account_select" class="fw-semibold">Customer
                                                Account:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <select class="form-select form-control max-select" id="customer_account_select"
                                                data-select2-selector="tag" name="customer_account_id">
                                                <option value="" disabled hidden>Pilih customer account</option>

                                                @if ($saleReturn->customer && $saleReturn->customer->accounts)
                                                    @foreach ($saleReturn->customer->accounts as $account)
                                                        <option value="{{ $account->id }}"
                                                            {{ $saleReturn->customer_account_id == $account->id ? 'selected' : '' }}>
                                                            {{ $account->name ?? '-' }} -
                                                            {{ $account->whatsapp_number ?? '-' }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="addresses" class="fw-semibold">Address:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <select class="form-select form-control max-select" id="addresses"
                                                data-select2-selector="tag" name="customer_address_id">
                                                @if ($order->customer)
                                                    @foreach ($order->customer->addresses as $index => $address)
                                                        <option value="{{ $address->id }}"
                                                            data-map="{{ $address->google_maps }}"
                                                            {{ $saleReturn->customer_address_id == $address->id ? 'selected' : '' }}>
                                                            {{ $address->business_name ?? 'None' }} -
                                                            {{ $address->address }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div id="google-maps-link" class="mt-1">
                                                @if ($saleReturn->customerAddress && $saleReturn->customerAddress->google_maps)
                                                    <a href="{{ $saleReturn->customerAddress->google_maps }}"
                                                        target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                                        Lihat di Google Maps
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" value="13" name="transaction_type" id="transaction_type">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="note" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea name="note" id="note" class="form-control" rows="2"
                                                    placeholder="Tambahkan catatan (opsional)">{{ $order->note }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="edit_note" class="fw-semibold">Edit Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <textarea class="form-control" id="edit_note" name="edit_note" rows="2"
                                                    placeholder="Tambahkan catatan edit..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="row">
                            <div class="col-lg-12">
                                <h5 class="fw-bold mb-2">Add Products:</h5>
                                <div class="product-grid product-grid-header mb-1">
                                    <div class="product-col-span-2">Product</div>
                                    <div>Canceled Qty</div>
                                    <div>Defect Qty</div>
                                    <div>Price</div>
                                    <div>Total</div>
                                </div>

                                <div id="product_list">
                                    @foreach ($remainingItems as $index => $item)
                                        <div class="product-item" data-index="{{ $index }}"
                                            data-oid="{{ $item->order_item_id }}">
                                            <div class="product-grid">
                                                <input type="hidden" name="order_item_ids[]"
                                                    value="{{ $item->order_item_id }}">

                                                <div class="form-group product-col-span-2">
                                                    <label>Product</label>
                                                    <select class="form-control select-product" name="product_id[]"
                                                        data-row-price="{{ $item->price }}">
                                                        <option value="" disabled hidden>Pilih produk</option>
                                                        {{-- Katalog dirender sekali di #product_options_template lalu
                                                             disalin fillAllProductOptions(), yang juga menempelkan
                                                             data-row-price di atas ke tiap opsi. --}}
                                                        @if ($item->product_id)
                                                            <option value="{{ $item->product_id }}"
                                                                data-price="{{ $item->price }}" selected>
                                                                [{{ optional($item->product)->sku }}]
                                                                {{ optional($item->product)->name }}
                                                            </option>
                                                        @endif
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label>Canceled Qty</label>
                                                    <input type="text" inputmode="numeric" name="canceled_quantity[]"
                                                        class="form-control canceled_quantity"
                                                        value="{{ number_format($item->canceled_quantity, 0, ',', '.') }}">
                                                    <small class="text-muted">
                                                        Sisa max: {{ number_format($item->remaining_qty ?? 0) }}
                                                    </small>
                                                    <div class="invalid-feedback text-danger small"></div>
                                                </div>

                                                <div class="form-group">
                                                    <label>Defect Qty</label>
                                                    <input type="text" inputmode="numeric" name="defect_quantity[]"
                                                        class="form-control defect_quantity"
                                                        value="{{ number_format($item->defect_quantity, 0, ',', '.') }}">
                                                    <div class="invalid-feedback text-danger small"></div>
                                                </div>

                                                <div class="form-group">
                                                    <label>Price</label>
                                                    <input type="text" inputmode="numeric"
                                                        class="price_display form-control"
                                                        value="{{ number_format($item->price) }}">
                                                    <input type="hidden" name="price[]" class="price"
                                                        value="{{ $item->price }}">
                                                </div>

                                                <div class="form-group">
                                                    <label>Total</label>
                                                    <input type="text" class="total_display form-control" readonly
                                                        value="{{ number_format($item->total) }}">
                                                    <input type="hidden" name="total[]" class="total"
                                                        value="{{ $item->total }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Satu-satunya tempat katalog produk dirender penuh. Isi <template>
                                     tidak ikut ter-submit dan tidak dirender browser; JS menyalinnya
                                     ke tiap select saat halaman siap. --}}
                                <template id="product_options_template">
                                    <select class="select-product">
                                        <option value="" disabled selected hidden>Pilih produk</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">
                                                [{{ $product->sku }}] {{ $product->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </template>
                            </div>
                            <div class="col-lg-12 mt-2">
                                <div class="row justify-content-end">
                                    <div class="col-lg-4">
                                        <table class="table table-bordered">
                                            <tbody>
                                                <tr>
                                                    <td><input type="hidden" name="sub_total" id="sub_total"
                                                            class="form-control" readonly></td>
                                                </tr>
                                                <tr>
                                                    <th class="bg-gray-100">Grand Total</th>
                                                    <td class="bg-gray-100">
                                                        <input type="text" id="total_amount_display"
                                                            class="form-control fw-700 text-dark" readonly>
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
                    {{-- <div class="card stretch stretch-full">
                    </div> --}}
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @include('erp.pages.partials.product-options-filler', [
        'templateId' => 'product_options_template',
        'containerSelector' => '#product_list',
    ])

    <script>
        const isOwner = {{ Auth::user()->role === 'Owner' ? 'true' : 'false' }};

        const customerAddresses = <?php echo json_encode(
            $customers->mapWithKeys(function ($customer) {
                return [
                    $customer->id => $customer->addresses->map(function ($address) {
                        return [
                            'id' => $address->id,
                            'address' => $address->address,
                            'google_maps' => $address->google_maps,
                        ];
                    }),
                ];
            }),
        ); ?>;

        const customerAccounts = <?php echo json_encode(
            $customers->mapWithKeys(function ($customer) {
                return [
                    $customer->id => $customer->accounts->map(function ($account) {
                        return [
                            'id' => $account->id,
                            'name' => $account->name,
                            'whatsapp_number' => $account->whatsapp_number,
                        ];
                    }),
                ];
            }),
        ); ?>;

        function formatNumber(num) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(num);
        }

        function updateRowTotal(row) {
            const canceled = parseFloat(row.find('.canceled_quantity').val().replace(/\D/g, '') || 0);
            const defect = parseFloat(row.find('.defect_quantity').val().replace(/\D/g, '') || 0);
            const price = parseFloat(row.find('.price').val().replace(/\D/g, '') || 0);
            const total = (canceled + defect) * price;

            // Update hidden dan tampilan
            row.find('.price').val(price.toFixed(0));
            row.find('.total').val(total.toFixed(0));

            row.find('.price_display').val(formatNumber(price));
            row.find('.total_display').val(formatNumber(total));

            calcTotal();
        }

        function calcTotal() {
            let sub = 0;
            $('.total').each(function() {
                sub += parseFloat($(this).val()) || 0;
            });

            $('#sub_total').val(sub.toFixed(0));
            $('#total_amount').val(sub.toFixed(0));

            if ($('#sub_total_display').length) {
                $('#sub_total_display').val(formatNumber(sub));
            }
            $('#total_amount_display').val(formatNumber(sub));
        }

        $(document).ready(function() {
            // Salin katalog dari <template> ke tiap baris sebelum select2
            // membaca isi select-nya.
            fillAllProductOptions();

            $('.select-product').select2({
                theme: 'bootstrap-5',
                placeholder: 'Pilih produk',
                width: '100%'
            });

            $('.product-item').each(function() {
                const row = $(this);
                const sel = row.find('select[name="product_id[]"]');
                const price = parseFloat(sel.find('option:selected').data('price')) || 0;

                row.find('.price').val(price.toFixed(0));
                row.find('.price_display').val(formatNumber(price));
                updateRowTotal(row);
            });

            const initialCustomerId = $('#customer_id').val();

            if (initialCustomerId) {
                updateAddresses(initialCustomerId);
                updateCustomerAccounts(initialCustomerId);
            }

            $('#customer_id').on('change', function() {
                updateAddresses($(this).val());
                updateCustomerAccounts($(this).val());
            });

            $('#addresses').on('change', updateGoogleMapsLink);

            function updateAddresses(customerId) {
                const addresses = customerAddresses[customerId] || [];
                const $addressSelect = $('#addresses');
                const selectedAddressId = "{{ $saleReturn->customer_address_id ?? '' }}";

                $addressSelect.empty().append('<option disabled hidden>Pilih alamat</option>');

                addresses.forEach(function(address, index) {
                    const isSelected = address.id == selectedAddressId;
                    $addressSelect.append(
                        `<option value="${address.id}" data-map="${address.google_maps}" ${isSelected ? 'selected' : ''}>
                            ${address.business_name ?? 'None'} - ${address.address}
                        </option>`
                    );
                });

                updateGoogleMapsLink();
            }

            function updateCustomerAccounts(customerId) {
                const accounts = customerAccounts[customerId] || [];
                const $accountSelect = $('#customer_account_select');
                const selectedAccountId = "{{ $saleReturn->customer_account_id ?? '' }}";

                $accountSelect.empty().append('<option value="" disabled hidden>Pilih customer account</option>');

                accounts.forEach(function(account) {
                    const isSelected = account.id == selectedAccountId;

                    $accountSelect.append(`
                        <option value="${account.id}" ${isSelected ? 'selected' : ''}>
                            ${(account.name ?? '-')} - ${(account.whatsapp_number ?? '-')}
                        </option>
                    `);
                });

                $accountSelect.trigger('change');
            }

            function updateGoogleMapsLink() {
                const selectedOption = $('#addresses').find('option:selected');
                const mapUrl = selectedOption.data('map');

                if (mapUrl) {
                    $('#google-maps-link').html(`
                    <a href="${mapUrl}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                        Lihat di Google Maps
                    </a>
                `);
                } else {
                    $('#google-maps-link').empty();
                }
            }
        });

        function showError(el, message) {
            if ($(el).hasClass('select2-hidden-accessible')) {
                const select2Container = $(el).next('.select2');
                select2Container.next('.invalid-feedback').remove();

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block';
                feedback.textContent = message;
                select2Container[0].after(feedback);
            } else {
                el.classList.add('is-invalid');
                const container = el.closest('.input-group') || el.parentNode;
                const existing = container.querySelector('.invalid-feedback');
                if (existing) existing.remove();

                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = message;
                feedback.style.display = 'block';
                container.appendChild(feedback);
            }
        }

        $('#orderForm').on('submit', function(e) {
            let isValid = true;
            $('.invalid-feedback').remove(); // hapus semua pesan error lama
            $('.is-invalid').removeClass('is-invalid');

            // 🔹 Validasi Edit Note wajib diisi
            const editNote = document.getElementById('edit_note');
            if (!editNote.value.trim()) {
                isValid = false;
                $(editNote).addClass('is-invalid');
                $(editNote).after(
                    '<div class="invalid-feedback d-block text-danger small mt-1">Catatan edit wajib diisi.</div>'
                );
            }

            // 🔹 Validasi baris produk
            $('.product-item').each(function() {
                const row = $(this);
                const canceledInput = row.find('.canceled_quantity');
                const defectInput = row.find('.defect_quantity');
                const priceInput = row.find('.price_display');

                const canceled = parseInt(canceledInput.val().replace(/\D/g, '') || 0);
                const defect = parseInt(defectInput.val().replace(/\D/g, '') || 0);
                const price = parseFloat(priceInput.val().replace(/\D/g, '') || 0);
                const maxQty = parseInt(row.find('small.text-muted').text().replace(/\D/g, '') || 0);
                const totalReturn = canceled + defect;

                // 🔹 Cek: wajib isi salah satu qty
                if (canceled === 0 && defect === 0) {
                    isValid = false;
                    canceledInput.addClass('is-invalid');
                    defectInput.addClass('is-invalid');

                    canceledInput.after(
                        '<div class="invalid-feedback d-block text-danger small mt-1">Isi salah satu quantity (Canceled / Defect).</div>'
                    );
                    defectInput.after(
                        '<div class="invalid-feedback d-block text-danger small mt-1">Isi salah satu quantity (Canceled / Defect).</div>'
                    );
                }

                // 🔹 Cek: tidak boleh melebihi sisa max
                if (totalReturn > maxQty) {
                    isValid = false;
                    canceledInput.addClass('is-invalid');
                    defectInput.addClass('is-invalid');

                    canceledInput.after(
                        `<div class="invalid-feedback d-block text-danger small mt-1">Total return (${totalReturn}) melebihi sisa max (${maxQty}).</div>`
                    );
                    defectInput.after(
                        `<div class="invalid-feedback d-block text-danger small mt-1">Total return (${totalReturn}) melebihi sisa max (${maxQty}).</div>`
                    );
                }

                // 🔹 Cek: harga wajib diisi dan tidak boleh nol
                if (!price || price <= 0) {
                    isValid = false;
                    priceInput.addClass('is-invalid');
                    priceInput.after(
                        '<div class="invalid-feedback d-block text-danger small mt-1">Harga wajib diisi dan tidak boleh 0.</div>'
                    );
                }
            });

            (function ensureOrderItemIds() {
                // hapus semua yang lama biar gak dobel
                $('input[name="order_item_ids[]"]').remove();

                $('.product-item').each(function() {
                    const oid = $(this).data('oid');
                    if (oid) {
                        $('<input>', {
                            type: 'hidden',
                            name: 'order_item_ids[]',
                            value: oid
                        }).appendTo('#orderForm');
                    }
                });
            })();

            // 🔹 Scroll ke field error pertama biar user langsung lihat
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }

            if (!isValid) {
                e.preventDefault();

                // 🔹 Scroll ke field error pertama biar user langsung lihat
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }

                // ❌ Tidak perlu SweetAlert lagi
                return;
            }

            // 🔹 Bersihkan format angka sebelum submit
            $('input[name="canceled_quantity[]"], input[name="defect_quantity[]"], input[name="price[]"], input[name="total[]"]')
                .each(function() {
                    $(this).val($(this).val().replace(/[.,]/g, ''));
                });
        });


        $(document).on('change', 'select[name="product_id[]"]', function() {
            const row = $(this).closest('.product-item');
            const price = parseFloat($(this).find('option:selected').data('price')) || 0;

            row.find('.price').val(price.toFixed(0));
            row.find('.price_display').val(formatNumber(price));
            updateRowTotal(row);
        });

        // === Harga bisa diubah hanya oleh Owner ===
        let priceEditTimeout;
        $(document).on('input', '.price_display', function() {
            if (!isOwner) return; // Non-owner gak bisa ubah harga

            const row = $(this).closest('.product-item');
            let rawValue = $(this).val().replace(/\D/g, '');
            if (rawValue.length > 12) rawValue = rawValue.substring(0, 12);

            const formatted = new Intl.NumberFormat('id-ID').format(rawValue);
            $(this).val(formatted);

            clearTimeout(priceEditTimeout);
            priceEditTimeout = setTimeout(() => {
                const parsed = parseFloat(rawValue) || 0;
                row.find('input.price').val(parsed.toFixed(0));
                updateRowTotal(row);
            }, 200);
        });

        $(document).on('blur', '.price_display', function() {
            let val = $(this).val().replace(/\D/g, '');
            $(this).val(new Intl.NumberFormat('id-ID').format(val));
        });

        // $('#orderForm').on('submit', function() {
        //     $('.qty').each(function() {
        //         const raw = $(this).val().replace(/,/g, '');
        //         $(this).val(raw);
        //     });
        // });

        $(document).on('input', '.price', function() {
            updateRowTotal($(this).closest('.product-item'));
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });

        $(document).on('input', '.canceled_quantity, .defect_quantity', function() {
            const row = $(this).closest('.product-item');

            // ambil nilai dari 2 kolom quantity
            let canceled = parseInt(row.find('.canceled_quantity').val().replace(/\D/g, '') || 0);
            let defect = parseInt(row.find('.defect_quantity').val().replace(/\D/g, '') || 0);

            // ambil batas maksimum dari teks "Sisa max: 1.000"
            const maxQty = parseInt(row.find('small.text-muted').text().replace(/\D/g, '') || 0);
            const totalReturn = canceled + defect;

            // kalau total melebihi maxQty, batasi nilainya dan tampilkan toast
            if (totalReturn > maxQty) {
                const remaining = maxQty - (this.classList.contains('canceled_quantity') ? defect : canceled);
                const corrected = Math.max(0, remaining);
                $(this).val(new Intl.NumberFormat('id-ID').format(corrected));

                // tampilkan toast
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'warning',
                    title: `Total return melebihi sisa max (${formatNumber(maxQty)}).`,
                    showConfirmButton: false,
                    timer: 2000,
                });

                // update variabel agar perhitungan total tetap benar
                if ($(this).hasClass('canceled_quantity')) {
                    canceled = corrected;
                } else {
                    defect = corrected;
                }
            }

            // format angka kembali
            $(this).val(new Intl.NumberFormat('id-ID').format($(this).val().replace(/\D/g, '')));

            updateRowTotal(row);
        });
    </script>
@endpush
