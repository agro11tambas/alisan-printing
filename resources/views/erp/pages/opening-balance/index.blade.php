@extends('erp.layouts.main')

@push('styles')
    <style>
        @media (max-width: 768px) {

            #openingBalanceList td.desktop-only,
            #openingBalanceList th.desktop-only {
                display: none !important;
            }
        }

        #openingBalanceList {
            width: 100% !important;
            min-width: 0;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Opening Balance</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Opening Balance</li>
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
                    <a href="/erp/accounts/opening-balance/edit-opening-balance" class="btn btn-primary">
                        <i class="feather-plus me-2"></i>
                        <span>Create Opening Balance</span>
                    </a>
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
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: "{{ session('success') }}",
            });
        </script>
    @endif
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
            <div class="col-lg-12">
                <div class="card stretch stretch-full">
                    <div class="card-body px-0 py-2">
                        <div class="row px-2 mb-2">
                            <div class="col-lg-12">
                                <h4>Opening Balance</h4>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table" id="openingBalanceList">
                                <thead>
                                    <tr>
                                        <th>Particular</th>
                                        <th>Debit</th>
                                        <th>Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="fw-bold bg-secondary text-white">Bank</td>
                                    </tr>
                                    @foreach ($bankAccounts as $bankAccount)
                                        <tr>
                                            <td>{{ $bankAccount->type }}</td>
                                            <td>{{ number_format($bankAccount->openingBalance->sum('debit'), 0, ',', '.') }}
                                            </td>
                                            <td>{{ number_format($bankAccount->openingBalance->sum('credit'), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" class="fw-bold bg-secondary text-white">Cash</td>
                                    </tr>
                                    @foreach ($cashAccounts as $cashAccount)
                                        <tr>
                                            <td>{{ $cashAccount->type }}</td>
                                            <td>{{ number_format($cashAccount->openingBalance->sum('debit'), 0, ',', '.') }}
                                            </td>
                                            <td>{{ number_format($cashAccount->openingBalance->sum('credit'), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" class="fw-bold bg-secondary text-white">Purchase</td>
                                    </tr>
                                    @foreach ($purchaseAccounts as $purchaseAccount)
                                        <tr>
                                            <td>{{ $purchaseAccount->type }}</td>
                                            <td>{{ number_format($purchaseAccount->openingBalance->sum('debit'), 0, ',', '.') }}
                                            </td>
                                            <td>{{ number_format($purchaseAccount->openingBalance->sum('credit'), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" class="fw-bold bg-secondary text-white">Sales</td>
                                    </tr>
                                    @foreach ($saleAccounts as $saleAccount)
                                        <tr>
                                            <td>{{ $saleAccount->type }}</td>
                                            <td>{{ number_format($saleAccount->openingBalance->sum('debit'), 0, ',', '.') }}
                                            </td>
                                            <td>{{ number_format($saleAccount->openingBalance->sum('credit'), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" class="fw-bold bg-secondary text-white">Expense</td>
                                    </tr>
                                    @foreach ($expenseAccounts as $expenseAccount)
                                        <tr>
                                            <td>{{ $expenseAccount->type }}</td>
                                            <td>{{ number_format($expenseAccount->openingBalance->sum('debit'), 0, ',', '.') }}
                                            </td>
                                            <td>{{ number_format($expenseAccount->openingBalance->sum('credit'), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td colspan="3" class="fw-bold bg-secondary text-white">Capital</td>
                                    </tr>
                                    @foreach ($capitalAccounts as $capitalAccount)
                                        <tr>
                                            <td>{{ $capitalAccount->type }}</td>
                                            <td>{{ number_format($capitalAccount->openingBalance->sum('debit'), 0, ',', '.') }}
                                            </td>
                                            <td>{{ number_format($capitalAccount->openingBalance->sum('credit'), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal fade" id="modalDeleteOpeningBalance" tabindex="-1" aria-labelledby="deleteModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="formDeleteOpeningBalance">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white" id="deleteModalLabel">Hapus OpeningBalance</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus OpeningBalance <strong id="openingBalanceName"></strong>?</p>
                        <p class="text-muted">Data yang dihapus tidak dapat dikembalikan.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-md">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('modalDeleteOpeningBalance');
            const form = document.getElementById('formDeleteOpeningBalance');
            const nameHolder = document.getElementById('openingBalanceName');

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const url = button.getAttribute('data-url');

                form.action = url;
                nameHolder.textContent = name;
            });
        });
    </script>
@endpush
