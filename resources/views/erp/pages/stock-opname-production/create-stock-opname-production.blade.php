@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Stock Opname Production</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Production</li>
                <li class="breadcrumb-item">Stock Opname</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2">
                    <a href="/erp/productions/stock-opname" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-primary" form="stockOpnameForm">
                        <i class="feather-plus me-2"></i>Create Stock Opname
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="main-content">
        <div class="card stretch">
            <form action="/erp/productions/stock-opname/store" method="POST" id="stockOpnameForm">
                @csrf
                <div class="card-body">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Change Type</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Notes</th>
                                <th width="50"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr>
                                <td>
                                    <select name="items[0][product_id]" class="form-select select2-product">
                                        <option value="" disabled selected hidden>Select product</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}">[{{ $p->sku }}] {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="items[0][production_warehouse_id]" value="2">
                                </td>
                                <td>
                                    <select name="items[0][change]" class="form-select change-type">
                                        <option value="" disabled selected hidden>Select</option>
                                        <option value="available_quantity">Available Quantity</option>
                                        <option value="finished_product">Finished Product</option>
                                    </select>
                                </td>
                                <td>
                                    <!-- 🧩 tambahkan class yang sesuai di sini -->
                                    <input type="number" name="items[0][available_quantity]"
                                        class="form-control qty-field available_quantity d-none"
                                        placeholder="Available Quantity">
                                    <input type="number" name="items[0][finished_product]"
                                        class="form-control qty-field finished_product d-none"
                                        placeholder="Finished Product">
                                </td>
                                <td>
                                    <select name="items[0][status]" class="form-select">
                                        <option value="Gain">Gain</option>
                                        <option value="Loss">Loss</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="date" name="items[0][date]" value="{{ date('Y-m-d') }}"
                                        class="form-control">
                                </td>
                                <td>
                                    <input type="text" name="items[0][notes]" class="form-control" placeholder="Notes">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm removeRow">
                                        <i class="feather-trash-2"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <template id="rowTemplate">
                        <tr>
                            <td>
                                <select class="form-select select2-product">
                                    <option value="" disabled selected hidden>Select product</option>
                                    @foreach ($products as $p)
                                        <option value="{{ $p->id }}">[{{ $p->sku }}] {{ $p->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" value="2" class="warehouse-id">
                            </td>
                            <td>
                                <select class="form-select change-type">
                                    <option value="" disabled selected hidden>Select</option>
                                    <option value="available_quantity">Available Quantity</option>
                                    <option value="finished_product">Finished Product</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[0][available_quantity]"
                                    class="form-control qty-field available_quantity d-none"
                                    placeholder="Available Quantity">
                                <input type="number" name="items[0][finished_product]"
                                    class="form-control qty-field finished_product d-none" placeholder="Finished Product">
                            </td>
                            <td>
                                <select class="form-select status">
                                    <option value="Gain">Gain</option>
                                    <option value="Loss">Loss</option>
                                </select>
                            </td>
                            <td><input type="date" value="{{ date('Y-m-d') }}" class="form-control date"></td>
                            <td><input type="text" class="form-control notes" placeholder="Notes"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm removeRow"><i
                                        class="feather-trash-2"></i></button>
                            </td>
                        </tr>
                    </template>

                    <button type="button" class="btn btn-outline-primary" id="addRowBtn">
                        <i class="feather-plus"></i> Add Row
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function initSelect2(scope) {
            $(scope).find('.select2-product').each(function() {
                const $el = $(this);

                // Hindari double init
                if ($el.hasClass('select2-hidden-accessible')) return;

                $el.select2({
                    width: '100%',
                    dropdownParent: $(document.body) // ✅ render di body agar gak kepotong card/table
                });
            });
        }

        // === FORMAT ANGKA DENGAN TITIK RIBUAN ===
        document.addEventListener('input', function(e) {
            if (e.target.matches(
                    'input[name^="items"][name$="[available_quantity]"], input[name^="items"][name$="[finished_product]"]'
                )) {
                let raw = e.target.value.replace(/\D/g, '');
                e.target.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
            }
        });

        // === FORMAT ANGKA SAAT HALAMAN DIBUKA ===
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll(
                'input[name^="items"][name$="[available_quantity]"], input[name^="items"][name$="[finished_product]"]'
            ).forEach(el => {
                if (el.value.trim() !== '') {
                    el.value = new Intl.NumberFormat('id-ID').format(
                        parseFloat(el.value.replace(/\./g, '')) || 0
                    );
                }
            });
        });

        $(document).ready(function() {
            initSelect2(document);

            $(document).on('select2:open', () => {
                setTimeout(() => {
                    document.querySelector('.select2-container--open .select2-search__field')
                        ?.focus();
                }, 50);
            });

            let rowIndex = 1;

            $('#addRowBtn').on('click', function() {
                const tmpl = document.getElementById('rowTemplate');
                const clone = tmpl.content.cloneNode(true);
                const $row = $(clone).find('tr');

                $row.find('.select2-product').attr('name', `items[${rowIndex}][product_id]`);
                $row.find('.warehouse-id').attr('name', `items[${rowIndex}][production_warehouse_id]`);
                $row.find('.change-type').attr('name', `items[${rowIndex}][change]`);
                $row.find('.available_quantity').attr('name', `items[${rowIndex}][available_quantity]`);
                $row.find('.finished_product').attr('name', `items[${rowIndex}][finished_product]`);
                $row.find('.status').attr('name', `items[${rowIndex}][status]`);
                $row.find('.date').attr('name', `items[${rowIndex}][date]`);
                $row.find('.notes').attr('name', `items[${rowIndex}][notes]`);

                $('#itemsBody').append($row);
                initSelect2($row);
                rowIndex++;

                updateChangeFields($row);
            });

            $(document).on('click', '.removeRow', function() {
                $(this).closest('tr').remove();
            });

            $(document).on('change', '.change-type', function() {
                const row = $(this).closest('tr');
                row.find('.qty-field').addClass('d-none');
                const val = $(this).val();
                if (val) row.find(`.${val}`).removeClass('d-none');
            });

            function updateChangeFields(scope) {
                $(scope).find('.change-type').each(function() {
                    const val = $(this).val();
                    const row = $(this).closest('tr');
                    row.find('.qty-field').addClass('d-none');
                    if (val) row.find(`.${val}`).removeClass('d-none');
                });
            }

            updateChangeFields(document);
            // 💥 Tambahkan ini
            $('#itemsBody .change-type').trigger('change');

            $('#stockOpnameForm').on('submit', function() {
                $('input[name^="items"][name$="[available_quantity]"], input[name^="items"][name$="[finished_product]"]')
                    .each(function() {
                        $(this).val($(this).val().replace(/\./g, ''));
                    });
            });
        });
    </script>
@endpush
