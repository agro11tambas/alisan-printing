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
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/productions/material-request" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="requestStockForm">
                        <i class="feather-plus me-2"></i>
                        <span>Edit Request Stock</span>
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
    <div class="main-content">
        <div class="row">
            <div class="col-12">
                <form action="/erp/productions/material-request/update/{{ $materialRequest->id }}" method="POST"
                    id="requestStockForm">
                    @csrf
                    @method('PUT')
                    <div class="card stretch stretch-full">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="requested_by" class="fw-semibold">User:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="requested_by"
                                                    name="requested_by" value="{{ Auth::user()->name }}" required readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="row mb-3 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="requested_at" class="fw-semibold">Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="requested_at"
                                                    name="requested_at"
                                                    value="{{ old('requested_at', isset($materialRequest->requested_at) ? \Carbon\Carbon::parse($materialRequest->requested_at)->format('Y-m-d') : date('Y-m-d')) }}"
                                                    required>
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
                                                    <th class="text-center wd-100">Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tab_logic_body">
                                                @foreach ($materialRequest->items as $i => $item)
                                                    <tr id="row{{ $i }}">
                                                        <td>{{ $i + 1 }}</td>
                                                        <input type="hidden" name="item_id[]" value="{{ $item->id }}">
                                                        <td>
                                                            <select class="form-control select-product" name="product[]"
                                                                required>
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
                                                            <input type="number" name="qty[]" class="form-control qty"
                                                                min="1" value="{{ $item->requested_qty }}" required>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <button type="button" id="delete_row"
                                            class="btn btn-md bg-soft-danger text-danger">Delete</button>
                                        <button type="button" id="add_row" class="btn btn-md btn-primary">Add
                                            Items</button>
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
        const products = @json($productsJson);

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

        document.addEventListener('DOMContentLoaded', function() {
            let rowCount = <?php echo json_encode(count($materialRequest->items)); ?>;

            document.querySelectorAll('select.select-product').forEach(el => {
                initSelect2(el);
            });

            document.getElementById('add_row').addEventListener('click', function() {
                const tableBody = document.querySelector('#tab_logic_body');
                const newRow = document.createElement('tr');
                newRow.id = 'row' + rowCount;

                let options = '<option value="" disabled selected hidden>Pilih produk</option>';
                products.forEach(p => {
                    options += `<option value="${p.id}">[${p.sku || '-'}] ${p.name}</option>`;
                });

                newRow.innerHTML = `
                <td>${rowCount + 1}</td>
                <input type="hidden" name="item_id[]" value="">
                <td>
                    <select class="form-control select-product" name="product[]" required>
                        ${options}
                    </select>
                </td>
                <td>
                    <input type="number" name="qty[]" class="form-control qty" min="1" value="1" required>
                </td>
            `;

                tableBody.appendChild(newRow);

                const select = newRow.querySelector('.select-product');
                initSelect2(select);

                rowCount++;
            });

            document.getElementById('delete_row').addEventListener('click', function() {
                if (rowCount > 1) {
                    rowCount--;
                    document.getElementById('row' + rowCount)?.remove();
                }
            });
        });

        $(document).on('select2:open', () => {
            setTimeout(() => {
                document.querySelector('.select2-container--open .select2-search__field')?.focus();
            }, 50);
        });
    </script>
@endpush