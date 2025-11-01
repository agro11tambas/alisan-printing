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
                                            <th class="text-center" style="width:100px;">Qty</th>
                                            <th class="text-center" style="width:80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tab_logic_body">
                                        <tr id="addr0">
                                            <td>1</td>
                                            <td>
                                                <select class="form-control select-product" name="product[]" required>
                                                    <option value="" disabled selected hidden>Pilih produk</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control stock bg-light" readonly
                                                    value="0">
                                            </td>
                                            <td>
                                                <input type="text" inputmode="numeric" name="qty[]"
                                                    class="form-control qty text-start" min="0" value="0"
                                                    required>
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
                    'data-stock': item.inventory_stock ?? 0
                }).appendTo(selectEl);
            });
        }

        function initSelect2(el) {
            $(el).select2({
                placeholder: 'Pilih produk',
                width: '100%',
                matcher: (params, data) => {
                    if ($.trim(params.term) === '') return data;
                    return data.text.toLowerCase().includes(params.term.toLowerCase()) ? data : null;
                }
            });
            populateProducts(el);
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
            });

            // ✅ Hapus titik sebelum submit
            $('#requestStockForm').on('submit', function() {
                $('.qty').each(function() {
                    this.value = this.value.replace(/\./g, '');
                });
            });

            $(document).on('change', '.select-product', function() {
                const selected = $('option:selected', this);
                const stock = selected.data('stock') ?? 0;
                $(this).closest('tr').find('.stock').val(stock.toLocaleString('id-ID'));
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
        });

        // Autofokus pencarian select2
        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
