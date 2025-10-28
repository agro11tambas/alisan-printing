@extends('erp.layouts.main')

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Order</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Order</li>
                <li class="breadcrumb-item">Add Progress</li>
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
                    <a href="/erp/productions/waiting-list" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
                    <button type="submit" class="btn btn-primary" form="orderForm">
                        <i class="feather-plus me-2"></i>
                        <span>Add Progress</span>
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
                <form action="/erp/productions/waiting-list/progress-order/{{ $progress->id }}" method="POST"
                    id="orderForm">
                    @csrf
                    @method('POST')

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Invoice Number : <span>{{ $progress->order_number }}</span></h4>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="change_date" class="fw-semibold">Change Date:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <input type="date" class="form-control" id="change_date" name="change_date"
                                        value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                            </div>

                            <div class="row mb-3 align-items-center">
                                <div class="col-lg-2">
                                    <label for="notes" class="fw-semibold">Note:</label>
                                </div>
                                <div class="col-lg-10 mb-0">
                                    <textarea class="form-control" id="notes" name="notes" placeholder="Catatan"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Add Progress</h4>
                        </div>
                        <div class="card-body">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Available Stock</th>
                                        <th>Printing</th>
                                        <th>Operator</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($progress->items as $index => $item)
                                        <tr>
                                            <td>{{ $item->product->name }}</td>
                                            <td>
                                                <span class="fw-bold text-success">{{ $item->completed_quantity }}</span> /
                                                <span class="fw-bold text-primary">{{ $item->quantity }}</span>
                                            </td>
                                            <td><span
                                                    class="fw-bold text-danger">{{ $item->product->productionStocks->available_quantity }}</span>
                                            </td>
                                            <td>
                                                <input type="hidden"
                                                    name="items[{{ $index }}][order_progress_item_id]"
                                                    value="{{ $item->id }}">
                                                <input type="text" inputmode="numeric"
                                                    name="items[{{ $index }}][change_quantity]" class="form-control"
                                                    value="0" min="0"
                                                    max="{{ $item->quantity - $item->completed_quantity }}"
                                                    placeholder="Jumlah dicetak">
                                                <small class="text-muted">Sisa:
                                                    {{ number_format($item->quantity - $item->completed_quantity, 0, ',', '.') }}</small>
                                            </td>
                                            <td>
                                                <select name="items[{{ $index }}][operator_id]"
                                                    class="form-control operator-field">
                                                    <option value="">-- Pilih Operator --</option>
                                                    @foreach ($operators as $op)
                                                        <option value="{{ $op->id }}">{{ $op->name }}</option>
                                                    @endforeach
                                                </select>
                                                <small class="text-danger error-operator d-none">Operator wajib
                                                    dipilih</small>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $index }}][note]"
                                                    class="form-control" placeholder="Note">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {

            function formatNumber(n) {
                return n.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            $(document).on('focus', 'input[name^="items"][name$="[change_quantity]"]', function() {
                if ($(this).val() === '0') {
                    $(this).val('');
                }
            });

            $(document).on('blur', 'input[name^="items"][name$="[change_quantity]"]', function() {
                if ($(this).val().trim() === '') {
                    $(this).val('0');
                }
            });

            $(document).on('input', 'input[name^="items"][name$="[change_quantity]"]', function(e) {
                const input = e.target;
                const cursorPos = input.selectionStart;
                const raw = input.value.replace(/\./g, '');
                if (raw === '') return;

                const formatted = formatNumber(raw);
                const diff = formatted.length - input.value.length;
                input.value = formatted;
                input.setSelectionRange(cursorPos + diff, cursorPos + diff);
            });

            $('#orderForm').on('submit', function() {
                $('input[name^="items"][name$="[change_quantity]"]').each(function() {
                    this.value = this.value.replace(/\./g, '');
                });
            });

            $('#btnSubmitForm').on('click', function(e) {
                e.preventDefault();

                let isValid = true;
                $('.error-operator').addClass('d-none');

                $('.operator-field').each(function() {
                    if ($(this).val() === '') {
                        $(this).closest('td').find('.error-operator').removeClass('d-none');
                        isValid = false;
                    }
                });

                if (!isValid) return;

                $('#orderForm').trigger('submit');
            });
        });
    </script>
@endpush
