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
                                            <td><input type="number" name="qty[]" class="form-control qty" min="1"
                                                    value="1" required></td>
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
                    text: `[${item.sku || '-'}] ${item.name}`
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

        document.addEventListener('DOMContentLoaded', function() {
            let rowCount = 1;

            // Inisialisasi awal
            document.querySelectorAll('select.select-product').forEach(el => {
                populateProducts(el);
                initSelect2(el);
            });

            // Tambah baris baru
            document.getElementById('add_row').addEventListener('click', function() {
                const tableBody = document.getElementById('tab_logic_body');
                const newRow = document.createElement('tr');
                newRow.id = 'addr' + rowCount;

                newRow.innerHTML = `
            <td>${rowCount + 1}</td>
            <td>
                <select class="form-control select-product" name="product[]" required>
                    <option value="" disabled selected hidden>Pilih produk</option>
                </select>
            </td>
            <td><input type="number" name="qty[]" class="form-control qty" min="1" value="1" required></td>
            <td class="text-center">
                <div class="d-flex justify-content-center">
                    <button type="button" class="btn btn-danger delete-row">
                        <i class="feather-trash"></i>
                    </button>
                </div>
            </td>
        `;

                tableBody.appendChild(newRow);
                initSelect2(newRow.querySelector('.select-product'));
                rowCount++;
            });

            // Hapus baris
            $(document).on('click', '.delete-row', function() {
                $(this).closest('tr').remove();

                // reindex
                $('#tab_logic_body tr').each(function(index) {
                    $(this).find('td:first').text(index + 1);
                });
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush
