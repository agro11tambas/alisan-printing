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
            padding: 4px;
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
            padding: 5px;
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
    </style>
@endpush

@section('breadcrumb')
    <div class="page-header sticky-top">
        <div class="page-header-left d-flex align-items-center">
            <div class="page-header-title">
                <h5 class="m-b-10">Freight</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item"><a href="/erp/purchases/freight-payments">Freight Payment</a></li>
                <li class="breadcrumb-item">Payment History</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:history.back()" class="page-header-right-close-toggle">
                        <i class="feather-arrow-left me-2"></i><span>Back</span>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                    <a href="javascript:history.back()" class="btn btn-light-brand">
                        <i class="feather-arrow-left me-2"></i>
                        <span>Back</span>
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
    <div class="main-content m-0 m-md-2 m-lg-2 p-0 p-md-0 p-lg-0 pt-1 pt-md-0">
        <div class="row align-items-baseline">
            <div class="col-xxl-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            Payment History - Freight
                            {{ $bill->waybill_number ? 'Surat Jalan #' . $bill->waybill_number : '(tanpa no. surat jalan)' }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-4 mb-3">
                            <div>
                                <div class="fs-12 text-muted">Supplier</div>
                                <div class="fw-semibold">{{ $bill->supplier_name }}</div>
                            </div>
                            <div>
                                <div class="fs-12 text-muted">Invoice</div>
                                <div class="fw-semibold">{{ $bill->invoice_numbers ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="fs-12 text-muted">Freight Total</div>
                                <div class="fw-semibold">Rp. {{ number_format($bill->total_freight, 0, ',', '.') }}</div>
                            </div>
                            <div>
                                <div class="fs-12 text-muted">Freight Paid</div>
                                <div class="fw-semibold text-success">Rp.
                                    {{ number_format($bill->paid_amount, 0, ',', '.') }}</div>
                            </div>
                            <div>
                                <div class="fs-12 text-muted">Remaining</div>
                                <div class="fw-semibold text-danger">Rp.
                                    {{ number_format($bill->remaining_amount, 0, ',', '.') }}</div>
                            </div>
                        </div>

                        @forelse($payments as $payment)
                            @php
                                $groupId = $payment->transaction_group_id;
                                $trxGroup = $transactions->get($groupId) ?? collect();
                                $creditGroup = $trxGroup->where('credit', '>', 0);
                                $proofData = $payment->proof ? json_decode($payment->proof, true) : null;
                            @endphp

                            <div class="mb-2 border rounded" data-group="{{ $groupId }}">
                                <div class="d-flex justify-content-between align-items-center bg-light p-1">
                                    <span>
                                        <strong>Tanggal:</strong>
                                        {{ optional($payment->transaction_date)->format('d-m-Y') }}
                                    </span>
                                    <div class="d-flex gap-3">
                                        <button type="button" class="btn btn-sm btn-primary btn-edit-payment"
                                            data-bs-toggle="modal" data-bs-target="#modalEditPayment"
                                            data-group="{{ $groupId }}"
                                            data-date="{{ optional($payment->transaction_date)->format('Y-m-d') }}"
                                            data-amount="{{ (int) $payment->paid_amount }}"
                                            data-account="{{ $payment->cash_bank_account_id }}"
                                            data-note="{{ $payment->note }}" data-proof='@json($payment->proof)'>
                                            <i class="feather feather-edit-3 me-2"></i>Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success btn-verify-payment"
                                            data-group="{{ $groupId }}"
                                            data-date="{{ optional($payment->transaction_date)->format('d-m-Y') }}"
                                            data-amount="{{ number_format($payment->paid_amount, 0, ',', '.') }}">
                                            <i class="feather-check-circle me-1"></i> Verify
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger btn-delete-payment"
                                            data-id="{{ $payment->id }}"
                                            data-date="{{ optional($payment->transaction_date)->format('d-m-Y') }}"
                                            data-amount="{{ number_format($payment->paid_amount, 0, ',', '.') }}">
                                            <i class="feather-trash-2 me-1"></i> Delete
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered m-0">
                                        <thead>
                                            <tr>
                                                <th>Akun</th>
                                                <th>Credit</th>
                                                <th>Keterangan</th>
                                                <th>Proof</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if ($creditGroup->isNotEmpty())
                                                @foreach ($creditGroup as $trx)
                                                    <tr>
                                                        <td>{{ $trx->account->name ?? '-' }}
                                                            ({{ $trx->account->type ?? '' }})
                                                        </td>
                                                        <td>{{ number_format($trx->credit, 0, ',', '.') }}</td>
                                                        <td>{{ $trx->note }}</td>
                                                        <td class="text-center">
                                                            @php
                                                                $trxProof = $trx->proof
                                                                    ? json_decode($trx->proof, true)
                                                                    : null;
                                                            @endphp
                                                            @if (is_array($trxProof) && count($trxProof))
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-primary btn-preview-proof"
                                                                    data-proofs='@json($trxProof)'>
                                                                    <i class="feather-image me-1"></i> Preview
                                                                    ({{ count($trxProof) }})
                                                                </button>
                                                            @else
                                                                <span class="text-muted">No Proof</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if ($trx->verified)
                                                                <span class="badge bg-success">Verified</span>
                                                            @else
                                                                <span class="badge bg-secondary">Pending</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                {{-- Jurnalnya sudah tidak ada (mis. dihapus manual): nilai pembayarannya tetap ditampilkan. --}}
                                                <tr>
                                                    <td>{{ $payment->cashBankAccount->name ?? '-' }}
                                                        ({{ $payment->cashBankAccount->type ?? '' }})
                                                    </td>
                                                    <td>{{ number_format($payment->paid_amount, 0, ',', '.') }}</td>
                                                    <td>{{ $payment->note ?? 'Freight Payment' }}</td>
                                                    <td class="text-center">
                                                        @if (is_array($proofData) && count($proofData))
                                                            <button type="button"
                                                                class="btn btn-sm btn-outline-primary btn-preview-proof"
                                                                data-proofs='@json($proofData)'>
                                                                <i class="feather-image me-1"></i> Preview
                                                                ({{ count($proofData) }})
                                                            </button>
                                                        @else
                                                            <span class="text-muted">No Proof</span>
                                                        @endif
                                                    </td>
                                                    <td><span class="badge bg-secondary">Pending</span></td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                                <div class="px-1 pb-1 fs-12 text-muted">
                                    Dibuat oleh: {{ $payment->user->name ?? '-' }}
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">Belum ada pembayaran freight untuk surat jalan ini.</p>
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
                    <h5 class="modal-title">Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editPaymentForm">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="transaction_group_id" id="transaction_group_id">

                        <div class="mb-2">
                            <label>Tanggal</label>
                            <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control">
                        </div>

                        <div class="mb-2">
                            <label>Paid Amount</label>
                            <input type="text" name="paid_amount" id="edit_paid_amount" class="form-control">
                            <small class="text-muted">Isi 0 untuk menghapus pembayaran ini.</small>
                        </div>

                        <div class="mb-2">
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

                        {{-- 🔹 Preview bukti EXISTING (dari DB) --}}
                        <div id="payment_proof_preview" class="d-flex gap-2 flex-wrap mb-1"></div>

                        {{-- 🔹 Upload / Paste Proof baru --}}
                        <div class="mb-2">
                            <label class="fw-semibold">Upload / Paste Proof (optional):</label>

                            <div id="pasteProofArea" class="border rounded p-2 text-center"
                                style="min-height: 120px; cursor: pointer;">
                                <p class="text-muted small mb-1">
                                    Klik di sini lalu tekan <strong>Ctrl + V</strong> untuk paste screenshot bukti transfer
                                </p>
                                <div id="proofPreviewContainer" class="preview-list"></div>
                            </div>

                            <input type="file" id="payment_proof" name="payment_proof[]" multiple hidden
                                accept="image/jpg,image/jpeg,image/png,image/webp,application/pdf">
                        </div>

                        <div class="mb-2">
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

    {{-- 🔹 Modal Konfirmasi Verify --}}
    <div class="modal fade-scale" id="modalVerifyPayment" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title text-white">
                        <i class="feather-check-circle me-2"></i>Konfirmasi Verifikasi Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2">Apakah kamu yakin ingin menandai pembayaran berikut sebagai
                        <strong>Verified</strong>?
                    </p>
                    <ul class="list-unstyled mb-2">
                        <li><strong>Tanggal:</strong> <span id="verifyDate" class="text-dark"></span></li>
                        <li><strong>Jumlah:</strong> <span id="verifyAmount" class="text-dark"></span></li>
                    </ul>
                    <input type="hidden" id="verifyGroupId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btnConfirmVerify" class="btn btn-success">
                        <i class="feather-check-circle me-1"></i>Ya, Verify
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        function openLightbox(imgSrc) {
            const lbId = 'lightboxModal';
            let modalEl = document.getElementById(lbId);
            if (!modalEl) {
                const tpl = `
                <div class="modal fade" id="${lbId}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content bg-dark">
                            <div class="modal-body p-0 text-center">
                                <img id="lightboxImage" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" class="img-fluid" />
                            </div>
                        </div>
                    </div>
                </div>`;
                document.body.insertAdjacentHTML('beforeend', tpl);
                modalEl = document.getElementById(lbId);
            }
            const modal = new bootstrap.Modal(modalEl);
            modalEl.querySelector('#lightboxImage').src = imgSrc;
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editPaymentForm');
            const previewDiv = document.getElementById('payment_proof_preview');

            // ===== Isi modal Edit =====
            document.querySelectorAll('.btn-edit-payment').forEach(btn => {
                btn.addEventListener('click', function() {
                    const groupId = this.dataset.group || '';

                    document.getElementById('transaction_group_id').value = groupId;
                    document.getElementById('edit_transaction_date').value = this.dataset.date || '';
                    document.getElementById('edit_paid_amount').value =
                        new Intl.NumberFormat('id-ID').format(this.dataset.amount || 0);
                    document.getElementById('edit_cash_bank_account_id').value = this.dataset.account ||
                        '';
                    document.getElementById('edit_note').value = this.dataset.note || '';
                    form.action = `/erp/purchases/freight-payments/update-payment/${groupId}`;

                    previewDiv.innerHTML = '';

                    let proofs = [];
                    try {
                        proofs = JSON.parse(this.dataset.proof || 'null');
                        if (typeof proofs === 'string') proofs = JSON.parse(proofs);
                    } catch (e) {
                        proofs = [];
                    }

                    if (Array.isArray(proofs) && proofs.length) {
                        proofs.forEach(item => {
                            const wrapper = document.createElement('div');
                            wrapper.classList.add('text-center');

                            const img = document.createElement('img');
                            img.src = '/' + item.file;
                            img.classList.add('shadow-sm', 'rounded');
                            img.style.maxHeight = '120px';
                            img.style.maxWidth = '120px';
                            img.style.objectFit = 'cover';
                            img.onclick = () => openLightbox('/' + item.file);

                            const caption = document.createElement('small');
                            caption.classList.add('text-muted', 'd-block', 'mt-1');
                            caption.innerText = item.note ? `Note: ${item.note}` : 'No note';

                            wrapper.appendChild(img);
                            wrapper.appendChild(caption);
                            previewDiv.appendChild(wrapper);
                        });
                    } else {
                        previewDiv.innerHTML = '<span class="text-muted">No proof uploaded</span>';
                    }
                });
            });

            const paidInput = document.getElementById('edit_paid_amount');
            paidInput.addEventListener('input', function() {
                const angka = this.value.replace(/\D/g, '') || '0';
                this.value = new Intl.NumberFormat('id-ID').format(angka);
            });

            // ===== Preview bukti =====
            const multiProofModal = new bootstrap.Modal(document.getElementById('multiProofModal'));
            const multiProofContainer = $('#multiProofContainer');

            $(document).on('click', '.btn-preview-proof', function() {
                const proofs = JSON.parse($(this).attr('data-proofs') || '[]');
                multiProofContainer.html('');

                proofs.forEach(item => {
                    multiProofContainer.append(`
                        <div class="col-md-12 col-sm-12">
                            <div class="border rounded shadow-sm p-1 bg-white h-100 text-center">
                                <img src="/${item.file}" class="img-fluid rounded mb-1" style="max-height:400px;object-fit:contain;">
                                <p class="small text-muted mt-1 mb-0">Note: ${item.note || '-'}</p>
                            </div>
                        </div>
                    `);
                });

                multiProofModal.show();
            });

            // ===== Paste / upload bukti baru =====
            let pastedProofBlobs = [];
            const pasteArea = document.getElementById('pasteProofArea');
            const previewContainer = document.getElementById('proofPreviewContainer');
            const fileInput = document.getElementById('payment_proof');

            function addPreview(url) {
                const wrapper = document.createElement('div');
                wrapper.classList.add('preview-item');

                const img = document.createElement('img');
                img.src = url;
                img.classList.add('img-thumbnail');
                img.style.maxHeight = '150px';
                img.style.marginBottom = '5px';

                const noteInput = document.createElement('input');
                noteInput.type = 'text';
                noteInput.classList.add('form-control', 'form-control-sm', 'note-input');
                noteInput.placeholder = 'Tambahkan catatan...';

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-danger mt-1';
                removeBtn.innerHTML = '<i class="feather-x"></i> Hapus';
                removeBtn.onclick = function() {
                    const index = Array.from(previewContainer.children).indexOf(wrapper);
                    pastedProofBlobs.splice(index, 1);
                    wrapper.remove();
                };

                wrapper.appendChild(img);
                wrapper.appendChild(noteInput);
                wrapper.appendChild(removeBtn);
                previewContainer.appendChild(wrapper);
            }

            if (pasteArea) {
                pasteArea.setAttribute('tabindex', '0');

                pasteArea.addEventListener('click', (e) => {
                    if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'BUTTON') {
                        pasteArea.focus();
                    }
                });

                pasteArea.addEventListener('paste', (e) => {
                    if (e.target.classList.contains('note-input')) return;

                    e.preventDefault();

                    for (const item of e.clipboardData.items) {
                        if (item.type.indexOf('image') === 0) {
                            const blob = item.getAsFile();
                            pastedProofBlobs.push(blob);

                            const reader = new FileReader();
                            reader.onload = (event) => addPreview(event.target.result);
                            reader.readAsDataURL(blob);
                        }
                    }
                });
            }

            fileInput.addEventListener('change', (e) => {
                [...e.target.files].forEach(file => {
                    pastedProofBlobs.push(file);
                    addPreview(URL.createObjectURL(file));
                });
            });

            // ===== Submit edit =====
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(form);
                const notes = [];
                $('#proofPreviewContainer .note-input').each(function() {
                    notes.push($(this).val());
                });

                pastedProofBlobs.forEach((blob, index) => {
                    formData.append(`payment_proof[${index}]`, blob, `proof_${index + 1}.png`);
                    formData.append(`note_per_image[${index}]`, notes[index] || '');
                });

                formData.set('paid_amount', paidInput.value.replace(/\./g, ''));

                $.ajax({
                    url: form.getAttribute('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        $('#modalEditPayment').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Pembayaran berhasil diperbarui',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        setTimeout(() => window.location.reload(), 900);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ?? 'Terjadi kesalahan saat update'
                        });
                    }
                });
            });

            // ===== Delete =====
            $(document).on('click', '.btn-delete-payment', function() {
                const paymentId = $(this).data('id');
                const date = $(this).data('date');
                const amount = $(this).data('amount');

                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus pembayaran ini?',
                    html: `Tanggal <strong>${date}</strong>, jumlah <strong>Rp ${amount}</strong>.<br>Saldo kas/bank akan dikembalikan dan jurnalnya ikut dihapus.`,
                    showCancelButton: true,
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc3545'
                }).then(function(result) {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: `/erp/purchases/freight-payments/payment/${paymentId}`,
                        method: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            _method: 'DELETE'
                        },
                        success: function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(() => window.location.reload(), 900);
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: xhr.responseJSON?.message ?? 'Pembayaran gagal dihapus'
                            });
                        }
                    });
                });
            });

            // ===== Verify =====
            const modalVerify = new bootstrap.Modal(document.getElementById('modalVerifyPayment'));
            const verifyDate = document.getElementById('verifyDate');
            const verifyAmount = document.getElementById('verifyAmount');
            const verifyGroupId = document.getElementById('verifyGroupId');
            const btnConfirmVerify = document.getElementById('btnConfirmVerify');

            $(document).on('click', '.btn-verify-payment', function() {
                verifyGroupId.value = $(this).data('group');
                verifyDate.textContent = $(this).data('date');
                verifyAmount.textContent = 'Rp ' + $(this).data('amount');
                modalVerify.show();
            });

            btnConfirmVerify.addEventListener('click', function() {
                const groupId = verifyGroupId.value;
                btnConfirmVerify.disabled = true;
                btnConfirmVerify.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';

                fetch(`/erp/purchases/purchase-list/verify-payment/${groupId}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(async (res) => {
                        const data = await res.json();
                        if (!res.ok) throw data;

                        modalVerify.hide();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message ?? 'Payment berhasil diverifikasi',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        const groupBox = document.querySelector(`[data-group="${data.group_id}"]`);
                        if (groupBox) {
                            groupBox.querySelectorAll('tbody tr').forEach(tr => {
                                const statusCell = tr.cells[4];
                                if (statusCell) {
                                    statusCell.innerHTML =
                                        '<span class="badge bg-success">Verified</span>';
                                }
                            });

                            const btnVerify = groupBox.querySelector('.btn-verify-payment');
                            if (btnVerify) {
                                btnVerify.classList.remove('btn-success');
                                btnVerify.classList.add('btn-secondary');
                                btnVerify.disabled = true;
                                btnVerify.innerHTML = '<i class="feather-check me-1"></i> Verified';
                            }
                        }
                    })
                    .catch(err => {
                        modalVerify.hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: err.message ?? 'Terjadi kesalahan saat verifikasi'
                        });
                    })
                    .finally(() => {
                        btnConfirmVerify.disabled = false;
                        btnConfirmVerify.innerHTML =
                            '<i class="feather-check-circle me-1"></i>Ya, Verify';
                    });
            });
        });
    </script>
@endpush
