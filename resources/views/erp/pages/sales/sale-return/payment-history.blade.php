@extends('erp.layouts.main')
@push('styles')
    <style>
        .preview-list {
            display: block;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            background: #fafafa;
            border: 1px dashed #ccc;
            border-radius: 6px;
            padding: 8px;
        }

        .preview-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 12px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 10px;
            position: relative;
        }

        .preview-item img {
            width: 100%;
            height: auto;
            border-radius: 6px;
            object-fit: contain;
        }

        .preview-item .note-input {
            width: 100%;
            font-size: 13px;
        }

        .preview-item .btn-remove-proof {
            position: absolute;
            top: 6px;
            right: 6px;
            border: none;
            background: rgba(0, 0, 0, 0.6);
            color: #fff;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Sale Return</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Sale Return</li>
                <li class="breadcrumb-item">Payment History</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="/erp/sales/sale-return" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
                    </a>
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
        <div class="row align-items-baseline">
            <div class="col-xxl-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Payment History - Sale Return #{{ $saleReturn->order_number }}</h5>
                    </div>
                    <div class="card-body">
                        @forelse($transactions as $groupId => $trxGroup)
                            @php $creditGroup = $trxGroup->where('credit', '>', 0); @endphp
                            @if ($creditGroup->isNotEmpty())
                                <div class="mb-4 border rounded">
                                    <div class="d-flex justify-content-between align-items-center bg-light p-2">
                                        <span><strong>Tanggal:</strong>
                                            {{ \Carbon\Carbon::parse($creditGroup->first()->transaction_date)->format('d-m-Y') }}</span>
                                        <button type="button" class="btn btn-sm btn-primary btn-edit-payment"
                                            data-bs-toggle="modal" data-bs-target="#modalEditPayment"
                                            data-group="{{ $groupId }}"
                                            data-date="{{ \Carbon\Carbon::parse($creditGroup->first()->transaction_date)->format('Y-m-d') }}"
                                            data-amount="{{ $creditGroup->sum('credit') }}"
                                            data-account="{{ optional($creditGroup->first())->account_id }}"
                                            data-note="{{ $creditGroup->first()->note }}"
                                            data-proof='@json($creditGroup->first()->proof)'>
                                            <i class="feather feather-edit-3 me-2"></i>Edit
                                        </button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered m-0">
                                            <thead>
                                                <tr>
                                                    <th>Akun</th>
                                                    <th>Credit</th>
                                                    <th>Keterangan</th>
                                                    {{-- <th>Particular</th> --}}
                                                    <th>Proof</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($creditGroup as $trx)
                                                    <tr>
                                                        <td>{{ $trx->account->name ?? '-' }}
                                                            ({{ $trx->account->type ?? '' }})
                                                        </td>
                                                        <td>{{ number_format($trx->credit, 0, ',', '.') }}</td>
                                                        <td>{{ $trx->note }}</td>
                                                        {{-- <td>{{ $trx->particular }}</td> --}}
                                                        <td class="text-center">
                                                            @if ($trx->proof)
                                                                @php $proofData = json_decode($trx->proof, true); @endphp
                                                                @if (is_array($proofData))
                                                                    <button type="button"
                                                                        class="btn btn-sm btn-outline-primary btn-preview-proof"
                                                                        data-proofs='@json($proofData)'>
                                                                        <i class="feather-image me-1"></i> Preview
                                                                        ({{ count($proofData) }})
                                                                    </button>
                                                                @else
                                                                    <span class="text-muted">Unknown File</span>
                                                                @endif
                                                            @else
                                                                <span class="text-muted">No Proof</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-muted">Belum ada pembayaran refund untuk sale return ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    {{-- 🔹 Modal Edit Payment --}}
    <div class="modal fade-scale" id="modalEditPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Refund Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editPaymentForm">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="transaction_group_id" id="transaction_group_id">

                        <div class="mb-3">
                            <label>Tanggal</label>
                            <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Refund Amount</label>
                            <input type="text" name="paid_amount" id="edit_paid_amount" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>Cash/Bank Account</label>
                            <select name="cash_bank_account_id" id="edit_cash_bank_account_id" class="form-control">
                                <option value="">-- Pilih Akun --</option>
                                @foreach ($cashAccounts as $cash)
                                    <option value="{{ $cash->id }}">Cash - {{ $cash->type }}</option>
                                @endforeach
                                @foreach ($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}">Bank - {{ $bank->type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="fw-semibold">Upload / Paste Proof (optional):</label>
                            <div id="pasteProofArea" class="border rounded p-3 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-2">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot bukti refund
                                </p>
                                <div id="proofPreviewContainer" class="preview-list"></div>
                            </div>

                            <input type="file" id="payment_proof" name="payment_proof[]" multiple hidden
                                accept="image/jpg,image/jpeg,image/png,image/webp,application/pdf">
                        </div>

                        <div class="mb-3">
                            <label>Note</label>
                            <input type="text" name="note" id="edit_note" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- 🔹 Modal Preview Proof --}}
    <div class="modal fade" id="multiProofModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Proof Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div id="multiProofContainer" class="row g-4"></div>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let pastedProofBlobs = [];
            const form = document.getElementById('editPaymentForm');
            const pasteArea = document.getElementById('pasteProofArea');
            const previewContainer = document.getElementById('proofPreviewContainer');
            const fileInput = document.getElementById('payment_proof');

            // edit button
            document.querySelectorAll('.btn-edit-payment').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('transaction_group_id').value = this.dataset.group;
                    document.getElementById('edit_transaction_date').value = this.dataset.date;
                    document.getElementById('edit_paid_amount').value = new Intl.NumberFormat(
                        'id-ID').format(this.dataset.amount || 0);
                    document.getElementById('edit_cash_bank_account_id').value = this.dataset
                        .account;
                    document.getElementById('edit_note').value = this.dataset.note || '';
                    form.action = `/erp/sales/sale-return/update-payment/${this.dataset.group}`;
                    previewContainer.innerHTML = '';
                    pastedProofBlobs = [];
                });
            });

            // paste upload
            function addPreview(url, file) {
                const wrap = document.createElement('div');
                wrap.classList.add('preview-item');
                const img = document.createElement('img');
                img.src = url;
                img.classList.add('img-thumbnail');
                img.style.maxHeight = '150px';
                const noteInput = document.createElement('input');
                noteInput.type = 'text';
                noteInput.classList.add('form-control', 'form-control-sm', 'note-input');
                noteInput.placeholder = 'Tambahkan catatan...';
                const del = document.createElement('button');
                del.classList.add('btn-remove-proof');
                del.innerHTML = '×';
                del.onclick = () => {
                    pastedProofBlobs.splice([...previewContainer.children].indexOf(wrap), 1);
                    wrap.remove();
                };
                wrap.append(img, noteInput, del);
                previewContainer.append(wrap);
            }

            pasteArea.addEventListener('paste', e => {
                e.preventDefault();
                [...e.clipboardData.items].forEach(item => {
                    if (item.type.includes('image')) {
                        const blob = item.getAsFile();
                        pastedProofBlobs.push(blob);
                        const reader = new FileReader();
                        reader.onload = ev => addPreview(ev.target.result, blob);
                        reader.readAsDataURL(blob);
                    }
                });
            });

            fileInput.addEventListener('change', e => {
                [...e.target.files].forEach(f => {
                    pastedProofBlobs.push(f);
                    addPreview(URL.createObjectURL(f), f);
                });
            });

            // format angka
            const paidInput = document.getElementById('edit_paid_amount');
            paidInput.addEventListener('input', function() {
                let val = this.value.replace(/\D/g, '') || '0';
                this.value = new Intl.NumberFormat('id-ID').format(val);
            });

            // submit
            form.addEventListener('submit', e => {
                e.preventDefault();
                const data = new FormData(form);
                $('#proofPreviewContainer .note-input').each((i, el) => {
                    data.append(`note_per_image[${i}]`, $(el).val());
                });
                pastedProofBlobs.forEach((b, i) => data.append(`payment_proof[${i}]`, b,
                    `proof_${i + 1}.png`));
                data.set('paid_amount', paidInput.value.replace(/\./g, ''));
                $.ajax({
                    url: form.action,
                    method: 'POST',
                    data,
                    processData: false,
                    contentType: false,
                    success: r => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: r.message ?? 'Updated',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#modalEditPayment').modal('hide');
                        $.get(window.location.href, function(html) {
                            const newDoc = new DOMParser().parseFromString(html,
                                'text/html');
                            const updatedCard = newDoc.querySelector(
                                    `[data-group="${form.transaction_group_id.value}"]`)
                                ?.closest('.border.rounded');

                            if (updatedCard) {
                                // ganti card lama dengan versi baru dari server
                                const oldCard = document.querySelector(
                                    `[data-group="${form.transaction_group_id.value}"]`
                                )?.closest('.border.rounded');
                                if (oldCard) oldCard.replaceWith(updatedCard);
                            }
                        });
                    },
                    error: x => Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: x.responseJSON?.message ?? 'Terjadi kesalahan'
                    })
                });
            });

            // preview proof
            const multiProofModal = new bootstrap.Modal(document.getElementById('multiProofModal'));
            const multiProofContainer = document.getElementById('multiProofContainer');
            // ===== Preview Proof (2 kolom besar) =====
            document.addEventListener('DOMContentLoaded', function() {
                const multiProofModal = new bootstrap.Modal(document.getElementById('multiProofModal'));
                const multiProofContainer = document.getElementById('multiProofContainer');

                $(document).on('click', '.btn-preview-proof', function() {
                    const proofs = JSON.parse($(this).attr('data-proofs'));
                    const multiProofModal = new bootstrap.Modal($('#multiProofModal')[0]);
                    const multiProofContainer = $('#multiProofContainer');
                    multiProofContainer.html('');

                    proofs.forEach(item => {
                        const col = $(`
            <div class="col-md-6 col-sm-12">
                <div class="border rounded shadow-sm p-2 bg-white h-100 text-center">
                    <img src="/${item.file}" class="img-fluid rounded mb-2" style="max-height:400px;object-fit:contain;">
                    <p class="small text-muted mt-2 mb-0">Note: ${item.note || '-'}</p>
                </div>
            </div>
        `);
                        multiProofContainer.append(col);
                    });

                    multiProofModal.show();
                });
            });
        });
    </script>
@endpush
