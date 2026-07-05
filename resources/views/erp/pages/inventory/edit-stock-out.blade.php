@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock Out</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Warehouse</li>
                <li class="breadcrumb-item">Add Stock Out</li>
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
                    <a href="/erp/inventory/stock-out" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="stockInForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Stock Out</span>
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
                <form action="/erp/inventory/stock-out/update/{{ $stockOut->id }}" method="POST" id="stockInForm"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <!-- <input type="hidden" name="inventory_id" value="{{ $stockOut->id }}"> -->
                                            <label for="change_date" class="fw-semibold">Change Date:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="change_date"
                                                    name="change_date"
                                                    value="{{ old('change_date', \Carbon\Carbon::parse($stockOut->change_date)->format('Y-m-d')) }}"
                                                    required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="row mb-2 align-items-center">
                                        <div class="col-lg-2">
                                            <label for="notes" class="fw-semibold">Note:</label>
                                        </div>
                                        <div class="col-lg-10 mb-0">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="notes" name="notes"
                                                    value="{{ old('notes', $stockOut->notes) }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mb-2">
                                        <h5 class="fw-bold">Add Products:</h5>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered overflow-hidden" id="tab_logic">
                                            <thead>
                                                <tr class="single-item">
                                                    <th class="text-center wd-50">#</th>
                                                    <th class="text-center">Product</th>
                                                    <th class="text-center wd-150">Total Stock</th>
                                                    <th class="text-center wd-150">Stock Out</th>
                                                    <th class="text-center wd-150">Remaining</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tab_logic">
                                                @foreach ($stockOut->histories as $index => $history)
                                                    <tr id="addr{{ $index }}">
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>
                                                            <select class="form-control select-product"
                                                                data-select2-selector="status" name="product[]"
                                                                id="product_{{ $index }}">
                                                                <option value="" disabled
                                                                    {{ !$history->product_id ? 'selected' : '' }}>Pilih
                                                                    produk</option>
                                                                @foreach ($products as $product)
                                                                    <option value="{{ $product->id }}"
                                                                        data-stock="{{ $product->stock }}"
                                                                        {{ $history->product_id == $product->id ? 'selected' : '' }}>
                                                                        {{ $product->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="stock[]" class="form-control stock"
                                                                id="stock_{{ $index }}"
                                                                value="{{ $history->product->stock ?? 0 }}" readonly>
                                                        </td>
                                                        <td>
                                                            <input type="number" name="stock_out[]"
                                                                class="form-control stock_out"
                                                                id="stock_out_{{ $index }}"
                                                                value="{{ $history->stock_out }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" name="remaining[]"
                                                                class="form-control remaining text-danger"
                                                                id="remaining_{{ $index }}"
                                                                value="{{ ($history->product->stock ?? 0) - $history->stock_out }}"
                                                                readonly>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-2">
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
        // Saat produk dipilih
        $(document).on('change', 'select[name="product[]"]', function() {
            let selectedOption = $(this).find('option:selected');
            let stock = selectedOption.data('stock');

            let row = $(this).closest('tr');
            row.find('input[name="stock[]"]').val(stock);

            let stock_out = parseFloat(row.find('input[name="stock_out[]"]').val()) || 0;
            row.find('input[name="remaining[]"]').val((stock * stock_out).toFixed(2));

            calc_remaining(); // update remaining keseluruhan
        });


        // Saat stock_out berubah
        $(document).on('input', 'input[name="stock_out[]"]', function() {
            let row = $(this).closest('tr');
            let stock_out = parseFloat($(this).val()) || 0;
            let stock = parseFloat(row.find('input[name="stock[]"]').val()) || 0;
            row.find('input[name="remaining[]"]').val((stock_out * stock).toFixed(2));
            calc_remaining();
        });

        $(document).ready(function() {
            let i = parseInt(`{{ count($stockOut->histories ?? []) }}`);

            function initSelect2() {
                $('[data-select2-selector="status"]').select2({
                theme: 'bootstrap-5',
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

                $newRow.find('input[name="stock_out[]"]').attr('id', 'stock_out_' + i).val('');
                $newRow.find('input[name="stock[]"]').attr('id', 'stock_' + i).val('');
                $newRow.find('input[name="remaining[]"]').attr('id', 'remaining_' + i).val('');

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
                calc_remaining();
            });
        });

        function calc() {
            $("#tab_logic tbody tr").each(function(i, element) {
                var html = $(this).html();
                if (html != "") {
                    var stock_out = $(this).find(".stock_out").val();
                    var stock = $(this).find(".stock").val();
                    $(this)
                        .find(".remaining")
                        .val(stock - stock_out);
                    calc_remaining();
                }
            });
        }

        function calc_remaining() {
            remaining = 0;
            $(".remaining").each(function() {
                remaining += parseInt($(this).val());
            });
            $("#sub_remaining").val(remaining.toFixed(2));
            $("#remaining").val((remaining).toFixed(2));
        }
    </script>
@endpush
