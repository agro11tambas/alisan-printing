@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Request Stock</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Request Stock</li>
                <li class="breadcrumb-item">Create Request Stock</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/productions/material-request" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="requestStockForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Request Stock</span>
                    </button>
                </div>
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
                <form action="/erp/productions/material-request/store" method="POST" id="requestStockForm">
                    @csrf
                    @method('POST')

                    <div class="card stretch stretch-full mb-3">
                        <div class="card-body">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="requested_by" class="fw-semibold">User:</label>
                                </div>
                                <div class="col-lg-10">
                                    <input type="text" class="form-control" id="requested_by" name="requested_by"
                                        value="{{ Auth::user()->name }}" readonly>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="requested_at" class="fw-semibold">Date:</label>
                                </div>
                                <div class="col-lg-10">
                                    <input type="date" class="form-control" id="requested_at" name="requested_at"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="mb-4">
                                <h5 class="fw-bold">Add Products:</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered overflow-hidden" id="tab_logic">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width:50px;">#</th>
                                            <th class="text-center" style="width:450px;">Product</th>
                                            <th class="text-center" style="width:130px;">Stock Warehouse</th>
                                            <th class="text-center" style="width:130px;">Pending Waiting List</th>
                                            <th class="text-center" style="width:100px;">Qty</th>
                                            <th class="text-center" style="width:80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tab_logic_body">
                                        <tr id="addr0">
                                            <td>1</td>
                                            <td>
                                                <select class="form-control select-product" name="product[]">
                                                    <option value="" disabled selected hidden>Pilih produk</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control stock bg-light" readonly
                                                    value="0">
                                            </td>
                                            <td>
                                                <input type="text" class="form-control pending_waiting_list bg-light"
                                                    readonly value="0">
                                            </td>
                                            <td>
                                                <input type="text" inputmode="numeric" name="qty[]"
                                                    class="form-control qty text-start" min="0" value="0">
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <button type="button" class="btn btn-danger delete-row">
                                                        <i class="feather-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>

                                </table>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-3">
                                <button type="button" id="add_row" class="btn btn-md btn-primary">Add Items</button>
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
        const products = @json($productsJson);

        function populateProducts(selectEl) {
            $(selectEl).empty().append('<option value="" disabled selected hidden>Pilih produk</option>');
            products.forEach(item => {
                $('<option>', {
                    value: item.id,
                    text: `[${item.sku || '-'}] ${item.name}`,
                    'data-stock': item.inventory_stock ?? 0,
                    'data-pending-waiting-list': item.pending_waiting_list ?? 0,
                }).appendTo(selectEl);
            });
        }

        // function initSelect2(el) {
        //     $(el).select2({
        //         placeholder: 'Pilih produk',
        //         width: '100%',
        //         matcher: (params, data) => {
        //             if ($.trim(params.term) === '') return data;
        //             return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data : null;
        //         }
        //     });
        //     populateProducts(el);
        // }

        function initSelect2(el) {
            if (!$(el).hasClass('select2-hidden-accessible')) {
                $(el).select2({
                    placeholder: 'Pilih produk',
                    width: '100%',
                    matcher: (params, data) => {
                        if ($.trim(params.term) === '') return data;
                        return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data : null;
                    }
                });
            }
        }

        $(document).ready(function() {
            let rowCount = 1;

            // Inisialisasi select2 di baris pertama
            document.querySelectorAll('select.select-product').forEach(el => {
                populateProducts(el);
                initSelect2(el);
            });

            function formatNumber(n) {
                return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // ✅ Saat fokus: kosongkan 0 biar langsung bisa input
            $(document).on('focus', '.qty', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            // ✅ Saat kehilangan fokus: kembalikan ke 0 kalau kosong
            $(document).on('blur', '.qty', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });

            // ✅ Saat input angka, format otomatis
            $(document).on('input', '.qty', function(e) {
                const input = $(this);
                const raw = input.val().replace(/\./g, '');
                if (raw === '') return;

                const value = parseInt(raw);
                const formatted = formatNumber(value.toString());
                input.val(formatted);

                // Hapus error saat user mulai mengetik
                removeError(input);
            });

            // ✅ Hapus error saat produk dipilih
            $(document).on('change', '.select-product', function() {
                const selected = $('option:selected', this);
                const stock = selected.data('stock') ?? 0;
                $(this).closest('tr').find('.stock').val(stock.toLocaleString('id-ID'));

                // Hapus error saat produk dipilih
                removeError($(this));
            });

            // ✅ Fungsi untuk menampilkan error
            function showError(element, message) {
                const parent = element.closest('td');

                // Hapus error lama jika ada
                parent.find('.error-message').remove();
                element.addClass('is-invalid');

                // Tambah pesan error
                const errorDiv = $('<div class="error-message text-danger small mt-1"></div>').text(message);
                parent.append(errorDiv);
            }

            // ✅ Fungsi untuk menghapus error
            function removeError(element) {
                const parent = element.closest('td');
                parent.find('.error-message').remove();
                element.removeClass('is-invalid');
            }

            // ✅ Validasi sebelum submit
            $('#requestStockForm').on('submit', function(e) {
                e.preventDefault();

                let isValid = true;

                // Hapus semua error sebelumnya
                $('.error-message').remove();
                $('.is-invalid').removeClass('is-invalid');

                // Validasi setiap baris
                $('#tab_logic_body tr').each(function() {
                    const row = $(this);
                    const productSelect = row.find('.select-product');
                    const qtyInput = row.find('.qty');

                    // Validasi Product
                    if (!productSelect.val() || productSelect.val() === '') {
                        showError(productSelect, 'Produk wajib dipilih');
                        isValid = false;
                    }

                    // Validasi Qty
                    const qtyValue = qtyInput.val().replace(/\./g, '');
                    if (!qtyValue || qtyValue === '0' || parseInt(qtyValue) <= 0) {
                        showError(qtyInput, 'Qty harus lebih dari 0');
                        isValid = false;
                    }
                });

                // Jika valid, hapus titik dan submit
                if (isValid) {
                    $('.qty').each(function() {
                        this.value = this.value.replace(/\./g, '');
                    });
                    this.submit();
                } else {
                    // Scroll ke error pertama
                    const firstError = $('.is-invalid').first();
                    if (firstError.length) {
                        $('html, body').animate({
                            scrollTop: firstError.offset().top - 100
                        }, 300);
                    }
                }
            });

            // ✅ Tambah baris baru
            $('#add_row').on('click', function() {
                const tableBody = $('#tab_logic_body');
                const newRow = $(`
                    <tr id="addr${rowCount}">
                        <td>${rowCount + 1}</td>
                        <td>
                            <select class="form-control select-product" name="product[]" required>
                                <option value="" disabled selected hidden>Pilih produk</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control stock bg-light" readonly value="0">
                        </td>
                        <td>
                            <input type="text" class="form-control pending_waiting_list bg-light" readonly value="0">
                        </td>
                        <td>
                            <input type="text" inputmode="numeric" name="qty[]" class="form-control qty" value="0" required>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-danger delete-row">
                                    <i class="feather-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>           
                `);

                tableBody.append(newRow);

                // 🟢 Isi dulu produk baru
                populateProducts(newRow.find('.select-product'));

                // 🟢 Setelah ada option, baru aktifkan Select2
                initSelect2(newRow.find('.select-product'));

                rowCount++;
            });


            // ✅ Hapus baris
            $(document).on('click', '.delete-row', function() {
                $(this).closest('tr').remove();
                $('#tab_logic_body tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            });

            // 🧩 Produk yang dikirim dari halaman Report Items
            const selectedProducts = @json($selectedProducts ?? []);

            if (selectedProducts.length > 0) {
                const tableBody = $('#tab_logic_body');
                tableBody.empty(); // hapus baris default

                selectedProducts.forEach((item, index) => {
                    const newRow = $(`
                        <tr id="addr${index}">
                            <td>${index + 1}</td>
                            <td>
                                <select class="form-control select-product" name="product[]" required></select>
                            </td>
                            <td>
                                <input type="text" class="form-control stock bg-light" readonly value="${(item.inventory_stock ?? 0).toLocaleString('id-ID')}">
                            </td>
                            <td>
                                <input type="text" class="form-control pending_waiting_list bg-light" readonly value="${(item.pending_waiting_list ?? 0).toLocaleString('id-ID')}">
                            </td>
                            <td>
                                <input type="text" inputmode="numeric" name="qty[]" class="form-control qty" value="0" required>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn btn-danger delete-row">
                                        <i class="feather-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `);

                    tableBody.append(newRow);

                    const select = newRow.find('.select-product');

                    // 🔹 Populate dulu semua produk (masukkan option ke select)
                    populateProducts(select);

                    // 🔹 Set value produk yang dipilih
                    select.val(item.id);

                    // 🔹 Baru init Select2 (setelah ada valuenya)
                    initSelect2(select);
                });


                // Fokus ke input qty pertama agar user bisa langsung isi
                // setTimeout(() => {
                //     $('#tab_logic_body tr:first .qty').focus().select();
                // }, 200);
            }

            function getSelectedProductIds() {
                const selectedIds = [];
                $('.select-product').each(function() {
                    const val = $(this).val();
                    if (val) selectedIds.push(val);
                });
                return selectedIds;
            }

            function refreshAllSelectOptions() {
                const selectedIds = getSelectedProductIds();

                $('.select-product').each(function() {
                    const select = $(this);
                    const currentValue = select.val();
                    const currentText = select.find('option:selected').text(); // simpan text produk aktif
                    const currentStock = select.find('option:selected').data('stock') ?? 0;
                    // Hapus semua option
                    select.empty().append('<option value="" disabled hidden>Pilih produk</option>');

                    // Tambahkan kembali produk yang belum dipilih
                    products.forEach(item => {
                        // tampilkan semua produk kecuali yang sudah dipilih di baris lain
                        // tapi produk aktif tetap muncul
                        if (!selectedIds.includes(String(item.id)) || String(item.id) === String(
                                currentValue)) {
                            $('<option>', {
                                value: item.id,
                                text: `[${item.sku || '-'}] ${item.name}`,
                                'data-stock': item.inventory_stock ?? 0,
                                'data-pending-waiting-list': item.pending_waiting_list ?? 0,
                            }).appendTo(select);
                        }
                    });

                    // Kembalikan value & text produk yang aktif supaya tidak hilang
                    if (currentValue) {
                        select.val(currentValue);

                        // Pastikan teks tampil di UI Select2
                        const selectedOption = select.find(`option[value="${currentValue}"]`);
                        if (selectedOption.length === 0) {
                            // kalau option-nya sempat hilang, tambahkan ulang manual
                            $('<option>', {
                                value: currentValue,
                                text: currentText,
                                'data-stock': currentStock
                            }).appendTo(select);
                            select.val(currentValue);
                        }
                    }

                    // Refresh Select2
                    if (select.hasClass('select2-hidden-accessible')) {
                        select.trigger('change.select2');
                    }
                });
            }


            // 🔹 Event: Saat memilih produk baru
            $(document).on('change', '.select-product', function() {
                const currentSelect = $(this);
                const selectedVal = currentSelect.val();
                const selectedText = currentSelect.find('option:selected').text();
                const selectedStock = currentSelect.find('option:selected').data('stock') ?? 0;
                const selectedPending = currentSelect.find('option:selected').data(
                    'pending-waiting-list') ?? 0;


                // Update stok
                currentSelect.closest('tr').find('.stock').val(selectedStock.toLocaleString('id-ID'));
                currentSelect.closest('tr').find('.pending_waiting_list').val(selectedPending.toLocaleString('id-ID'));

                // 🚫 Cegah duplikasi produk di baris lain
                let duplicate = false;
                $('.select-product').not(currentSelect).each(function() {
                    if ($(this).val() === selectedVal) {
                        duplicate = true;
                    }
                });

                if (duplicate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Produk sudah dipilih!',
                        text: 'Produk ini sudah ada di baris lain.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    currentSelect.val('').trigger('change.select2');
                    currentSelect.closest('tr').find('.stock').val('0');
                    return;
                }
            });


            // 🔹 Event: Saat baris dihapus → update juga dropdown lain
            $(document).on('click', '.delete-row', function() {
                $(this).closest('tr').remove();
                $('#tab_logic_body tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            });
        });

        // Autofokus pencarian select2
        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
