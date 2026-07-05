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
                <h5 class="m-b-10">Purchase Return</h5>
            </div>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/erp/welcome">Home</a></li>
                <li class="breadcrumb-item">Purchase Return</li>
                <li class="breadcrumb-item">Payment History</li>
            </ul>
        </div>
        <div class="page-header-right ms-auto">
            <div class="page-header-right-items">
                <div class="d-flex d-md-none">
                    <a href="javascript:void(0)" class="page-header-right-close-toggle" onclick="goBack()">
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
        <div class="row align-items-baseline">
            <div class="col-xxl-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            Payment History - Purchase Return #{{ $purchaseReturn->order_number ?? $purchaseReturn->id }}
                        </h5>
                    </div>
                    <div class="card-body">
                        @forelse($transactions as $groupId => $trxGroup)
                            @php
                                $debitGroup = $trxGroup->where('debit', '>', 0);
                            @endphp

                            @if ($debitGroup->isNotEmpty())
                                <div class="mb-2 border rounded" data-group="{{ $groupId }}">
                                    <div class="d-flex justify-content-between align-items-center bg-light p-1">
                                        <span>
                                            <strong>Tanggal:</strong>
                                            {{ \Carbon\Carbon::parse($debitGroup->first()->transaction_date)->format('d-m-Y') }}
                                        </span>
                                        <div class="d-flex gap-3">
                                            <button type="button" class="btn btn-sm btn-primary btn-edit-payment"
                                                data-bs-toggle="modal" data-bs-target="#modalEditPayment"
                                                data-group="{{ $groupId }}"
                                                data-date="{{ \Carbon\Carbon::parse($debitGroup->first()->transaction_date)->format('Y-m-d') }}"
                                                data-amount="{{ $debitGroup->sum('debit') }}"
                                                data-account="{{ optional($debitGroup->first())->account_id }}"
                                                data-note="{{ $debitGroup->first()->note }}"
                                                data-proof='@json($debitGroup->first()->proof)'>
                                                <i class="feather feather-edit-3 me-2"></i>Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-success btn-verify-payment"
                                                data-group="{{ $groupId }}"
                                                data-date="{{ \Carbon\Carbon::parse($debitGroup->first()->transaction_date)->format('d-m-Y') }}"
                                                data-amount="{{ number_format($debitGroup->sum('debit'), 0, ',', '.') }}">
                                                <i class="feather-check-circle me-1"></i> Verify
                                            </button>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered m-0">
                                            <thead>
                                                <tr>
                                                    <th>Akun</th>
                                                    <th>Debit</th>
                                                    <th>Keterangan</th>
                                                    {{-- <th>Particular</th> --}}
                                                    <th>Proof</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($debitGroup as $trx)
                                                    <tr>
                                                        <td>{{ $trx->account->name ?? '-' }}
                                                            ({{ $trx->account->type ?? '' }})
                                                        </td>
                                                        <td>{{ number_format($trx->debit, 0, ',', '.') }}</td>
                                                        <td>{{ $trx->note }}</td>
                                                        {{-- <td>{{ $trx->particular }}</td> --}}
                                                        <td class="text-center">
                                                            @if ($trx->proof)
                                                                @php
                                                                    $proofData = json_decode($trx->proof, true);
                                                                @endphp

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
                                                        <td>
                                                            @if ($trx->verified)
                                                                <span class="badge bg-success">Verified</span>
                                                            @else
                                                                <span class="badge bg-secondary">Pending</span>
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
                            <p class="text-muted">Belum ada refund payment untuk purchase return ini.</p>
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
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="transaction_group_id" id="transaction_group_id">

                        <div class="mb-2">
                            <label>Tanggal</label>
                            <input type="date" name="transaction_date" id="edit_transaction_date" class="form-control">
                        </div>

                        <div class="mb-2">
                            <label>Refund Amount</label>
                            <input type="text" name="paid_amount" id="edit_paid_amount" class="form-control">
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

                            <small class="text-danger d-none" id="error_payment_proof"></small>
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

    {{-- 🔹 Modal Preview Proof (2 kolom besar) --}}
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
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editPaymentForm');
            const previewDiv = document.getElementById('payment_proof_preview');

            // Saat tombol Edit diklik
            document.querySelectorAll('.btn-edit-payment').forEach(btn => {
                btn.addEventListener('click', function() {
                    const groupId = this.dataset.group || '';
                    const date = this.dataset.date || '';
                    const amount = this.dataset.amount || '';
                    const account = this.dataset.account || '';
                    const note = this.dataset.note || '';
                    const proofRaw = this.dataset.proof || '';

                    // isi data ke form
                    document.getElementById('transaction_group_id').value = groupId;
                    document.getElementById('edit_transaction_date').value = date;
                    document.getElementById('edit_paid_amount').value =
                        new Intl.NumberFormat('id-ID').format(amount);
                    document.getElementById('edit_cash_bank_account_id').value = account;
                    document.getElementById('edit_note').value = note;
                    form.action = `/erp/purchases/purchase-returns/update-payment/${groupId}`;

                    // reset preview existing
                    previewDiv.innerHTML = '';

                    // render preview EXISTING proof (dari DB)
                    try {
                        const proofs = JSON.parse(proofRaw);
                        if (Array.isArray(proofs)) {
                            proofs.forEach(item => {
                                const fileUrl = '/' + item.file;
                                const noteText = item.note || '';

                                const wrapper = document.createElement('div');
                                wrapper.classList.add('text-center');

                                const img = document.createElement('img');
                                img.src = fileUrl;
                                img.classList.add('shadow-sm', 'rounded');
                                img.style.maxHeight = '120px';
                                img.style.maxWidth = '120px';
                                img.style.objectFit = 'cover';
                                img.onclick = () => openLightbox(fileUrl);

                                const caption = document.createElement('small');
                                caption.classList.add('text-muted', 'd-block', 'mt-1');
                                caption.innerText = noteText ? `Note: ${noteText}` :
                                    'No note';

                                wrapper.appendChild(img);
                                wrapper.appendChild(caption);
                                previewDiv.appendChild(wrapper);
                            });
                            return;
                        }
                    } catch (e) {
                        // bukan JSON, cek string tunggal di bawah
                    }

                    // kalau proof cuma satu file (string path)
                    if (proofRaw) {
                        const ext = proofRaw.split('.').pop().toLowerCase();
                        if (['jpg', 'jpeg', 'png', 'webp'].includes(ext)) {
                            previewDiv.innerHTML = `
                                <a href="javascript:void(0)" onclick="openLightbox('${proofRaw}')">
                                    <img src="${proofRaw}" alt="Proof" class="img-thumbnail shadow-sm" style="max-height: 100px;">
                                </a>`;
                        } else if (ext === 'pdf') {
                            previewDiv.innerHTML = `
                                <a href="${proofRaw}" target="_blank" class="btn btn-outline-danger btn-sm">
                                    <i class="feather-file-text me-1"></i> View PDF
                                </a>`;
                        } else {
                            previewDiv.innerHTML = `<span class="text-muted">Unknown File</span>`;
                        }
                    } else {
                        previewDiv.innerHTML = `<span class="text-muted">No proof uploaded</span>`;
                    }
                });
            });

            // format angka input
            const paidInput = document.getElementById('edit_paid_amount');
            paidInput.addEventListener('input', function() {
                let angka = this.value.replace(/\D/g, "") || "0";
                this.value = new Intl.NumberFormat('id-ID').format(angka);
            });

            // hapus titik sebelum submit (fallback non-AJAX)
            form.addEventListener('submit', function() {
                paidInput.value = paidInput.value.replace(/\./g, "");
            });
        });

        // helper lightbox
        function openLightbox(imgSrc) {
            const lbId = 'lightboxModal';
            let modalEl = document.getElementById(lbId);
            if (!modalEl) {
                const tpl = `
                <div class="modal fade" id="${lbId}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content bg-dark">
                            <div class="modal-body p-0 text-center">
                                <img id="lightboxImage" src="" class="img-fluid" />
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
            <div class="col-md-12 col-sm-12">
                <div class="border rounded shadow-sm p-1 bg-white h-100 text-center">
                    <img src="/${item.file}" class="img-fluid rounded mb-1" style="max-height:400px;object-fit:contain;">
                    <p class="small text-muted mt-1 mb-0">Note: ${item.note || '-'}</p>
                </div>
            </div>
        `);
                    multiProofContainer.append(col);
                });

                multiProofModal.show();
            });
        });

        // ===== Paste / Upload bukti + AJAX Submit =====
        document.addEventListener('DOMContentLoaded', function() {
            let pastedProofBlobs = [];
            const form = document.getElementById('editPaymentForm');
            const pasteArea = document.getElementById('pasteProofArea');
            const previewContainer = document.getElementById('proofPreviewContainer');
            const fileInput = document.getElementById('payment_proof');

            function addPreview(url, file) {
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
                noteInput.style.width = '100%';

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

                    // 🔥 Jika paste dilakukan di dalam input note ➜ biarkan paste normal
                    if (e.target.classList.contains('note-input')) {
                        return;
                    }

                    // 📌 Selain input note → intercept image paste
                    e.preventDefault();

                    const items = e.clipboardData.items;
                    for (const item of items) {
                        if (item.type.indexOf("image") === 0) {
                            const blob = item.getAsFile();
                            pastedProofBlobs.push(blob);

                            const reader = new FileReader();
                            reader.onload = function(event) {
                                addPreview(event.target.result, blob);
                            };
                            reader.readAsDataURL(blob);
                        }
                    }
                });
            }

            fileInput.addEventListener('change', (e) => {
                [...e.target.files].forEach(file => {
                    pastedProofBlobs.push(file);
                    const url = URL.createObjectURL(file);
                    addPreview(url, file);
                });
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const url = form.getAttribute('action');
                const formData = new FormData(form);

                const notes = [];
                $('#proofPreviewContainer .note-input').each(function() {
                    notes.push($(this).val());
                });

                // sesuai pola yang kamu minta: payment_proof[index] & note_per_image[index]
                pastedProofBlobs.forEach((blob, index) => {
                    formData.append(`payment_proof[${index}]`, blob, `proof_${index + 1}.png`);
                    formData.append(`note_per_image[${index}]`, notes[index] || '');
                });

                const paidInput = document.getElementById('edit_paid_amount');
                formData.set('paid_amount', paidInput.value.replace(/\./g, ""));

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message ?? 'Refund updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        $('#modalEditPayment').modal('hide');

                        if (res.status === 'deleted') {
                            const groupBox = document.querySelector(
                                `[data-group="${res.group_id}"]`);
                            if (groupBox) groupBox.remove(); // hapus card payment dari ui
                            return; // selesai, tidak perlu lanjut update UI
                        }

                        const data = res.data;
                        if (!data) return;

                        const groupBox = document.querySelector(
                            `[data-group="${data.transaction_group_id}"]`);
                        if (!groupBox) return;

                        // 🔹 Update tanggal di header
                        const dateEl = groupBox.querySelector('.bg-light span');
                        if (dateEl) {
                            dateEl.innerHTML =
                                `<strong>Tanggal:</strong> ${data.transaction_date}`;
                        }

                        // 🔹 Update isi tabel
                        const row = groupBox.querySelector('tbody tr');
                        if (row) {
                            row.cells[0].textContent =
                                `${data.account_name} (${data.account_type})`;
                            row.cells[1].textContent = data.paid_amount;
                            row.cells[2].textContent = data.note || '-';

                            // Update status badge jadi Pending (karena verified direset)
                            const statusTd = row.cells[4]; // kolom ke-5
                            if (statusTd) {
                                statusTd.innerHTML =
                                    `<span class="badge bg-secondary">Pending</span>`;
                            }

                            // Aktifkan kembali tombol Verify
                            const btnVerify = groupBox.querySelector('.btn-verify-payment');
                            if (btnVerify) {
                                btnVerify.disabled = false;
                                btnVerify.classList.remove('btn-secondary');
                                btnVerify.classList.add('btn-success');
                                btnVerify.innerHTML =
                                    `<i class="feather-check-circle me-1"></i> Verify`;
                            }

                            const proofTd = row.cells[3];
                            if (data.proofs && data.proofs.length > 0) {
                                proofTd.innerHTML = `
                <button type="button"
                    class="btn btn-sm btn-outline-primary btn-preview-proof"
                    data-proofs='${JSON.stringify(data.proofs)}'>
                    <i class="feather-image me-1"></i> Preview (${data.proofs.length})
                </button>`;
                            } else {
                                proofTd.innerHTML = `<span class="text-muted">No Proof</span>`;
                            }
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat update'
                        });
                    }
                });
            });

            // ============================
            // 🔹 MODAL VERIFY LOGIC
            // ============================
            const modalVerify = new bootstrap.Modal(document.getElementById('modalVerifyPayment'));
            const verifyDate = document.getElementById('verifyDate');
            const verifyAmount = document.getElementById('verifyAmount');
            const verifyGroupId = document.getElementById('verifyGroupId');
            const btnConfirmVerify = document.getElementById('btnConfirmVerify');

            // Klik tombol Verify → buka modal
            $(document).on('click', '.btn-verify-payment', function() {
                const group = $(this).data('group');
                const date = $(this).data('date');
                const amount = $(this).data('amount');

                verifyGroupId.value = group;
                verifyDate.textContent = date;
                verifyAmount.textContent = 'Rp ' + amount;
                modalVerify.show();
            });

            // Klik tombol "Ya, Verify" di modal
            btnConfirmVerify.addEventListener('click', function() {
                const groupId = verifyGroupId.value;
                btnConfirmVerify.disabled = true;
                btnConfirmVerify.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';

                fetch(`/erp/purchases/purchase-returns/verify-payment/${groupId}`, {
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
                            text: data.message ?? 'Refund berhasil diverifikasi',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // ✅ Update tampilan tanpa reload
                        const groupBox = document.querySelector(`[data-group="${data.group_id}"]`);
                        if (groupBox) {
                            groupBox.querySelectorAll('tbody tr').forEach(tr => {
                                const statusCell = tr.cells[4];
                                if (statusCell) {
                                    statusCell.innerHTML =
                                        `<span class="badge bg-success">Verified</span>`;
                                }
                            });

                            const btnVerify = groupBox.querySelector('.btn-verify-payment');
                            if (btnVerify) {
                                btnVerify.classList.remove('btn-success');
                                btnVerify.classList.add('btn-secondary');
                                btnVerify.disabled = true;
                                btnVerify.innerHTML = `<i class="feather-check me-1"></i> Verified`;
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
