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
                <li class="breadcrumb-item">Edit Request Stock</li>
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
                        <i class="feather-check me-2"></i>
                        <span>Update Request Stock</span>
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
                <form action="/erp/productions/material-request/update/{{ $materialRequest->id }}" method="POST"
                    id="requestStockForm">
                    @csrf
                    @method('PUT')

                    {{-- Info User & Date --}}
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
                                        value="{{ old('requested_at', \Carbon\Carbon::parse($materialRequest->requested_at)->format('Y-m-d')) }}"
                                        required>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Table Products --}}
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="mb-4">
                                <h5 class="fw-bold">Edit Products:</h5>
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
                                        @foreach ($materialRequest->items as $i => $item)
                                            <tr id="row{{ $i }}">
                                                <td>{{ $i + 1 }}</td>
                                                <input type="hidden" name="item_id[]" value="{{ $item->id }}">
                                                <td>
                                                    <select class="form-control select-product" name="product[]" required>
                                                        <option value="" disabled hidden>Pilih produk</option>
                                                        @foreach ($productsJson as $p)
                                                            <option value="{{ $p['id'] }}"
                                                                {{ $p['id'] == $item->product_id ? 'selected' : '' }}>
                                                                [{{ $p['sku'] ?? '-' }}] {{ $p['name'] }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" inputmode="numeric" name="qty[]"
                                                        class="form-control qty text-start" min="0"
                                                        value="{{ number_format($item->requested_qty, 0, ',', '.') }}"
                                                        required>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-danger delete-row">
                                                        <i class="feather-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
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
        }

        $(document).ready(function() {
            let rowCount = {{ count($materialRequest->items) }};

            $('select.select-product').each(function() {
                initSelect2(this);
            });

            function formatNumber(n) {
                return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            // Fokus & blur qty
            $(document).on('focus', '.qty', function() {
                if ($(this).val() === '0') $(this).val('');
            });

            $(document).on('blur', '.qty', function() {
                if ($(this).val().trim() === '') $(this).val('0');
            });

            // Input formatting
            $(document).on('input', '.qty', function(e) {
                const input = $(this);
                const raw = input.val().replace(/\./g, '');
                if (raw === '') return;

                const value = parseInt(raw);
                const formatted = formatNumber(value.toString());
                input.val(formatted);
            });

            // Bersihkan titik sebelum submit
            $('#requestStockForm').on('submit', function() {
                $('.qty').each(function() {
                    this.value = this.value.replace(/\./g, '');
                });
            });

            // Tambah baris baru
            $('#add_row').on('click', function() {
                const tableBody = $('#tab_logic_body');
                const newRow = $(`
                    <tr id="row${rowCount}">
                        <td>${rowCount + 1}</td>
                        <input type="hidden" name="item_id[]" value="">
                        <td>
                            <select class="form-control select-product" name="product[]" required>
                                <option value="" disabled selected hidden>Pilih produk</option>
                            </select>
                        </td>
                        <td>
                            <input type="text" inputmode="numeric" name="qty[]" class="form-control qty text-start" min="0" value="0" required>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger delete-row">
                                <i class="feather-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
                tableBody.append(newRow);
                populateProducts(newRow.find('.select-product'));
                initSelect2(newRow.find('.select-product'));
                rowCount++;
            });

            // Hapus baris
            $(document).on('click', '.delete-row', function() {
                $(this).closest('tr').remove();
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