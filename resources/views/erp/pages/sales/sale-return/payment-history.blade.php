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
                                <div class="mb-4 border rounded" data-group="{{ $groupId }}">
                                    <div class="d-flex justify-content-between align-items-center bg-light p-2">
                                        <span><strong>Tanggal:</strong>
                                            {{ \Carbon\Carbon::parse($creditGroup->first()->transaction_date)->format('d-m-Y') }}</span>
                                        <div class="d-flex gap-3">
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
                                            <button type="button" class="btn btn-sm btn-success btn-verify-payment"
                                                data-group="{{ $groupId }}"
                                                data-date="{{ \Carbon\Carbon::parse($creditGroup->first()->transaction_date)->format('d-m-Y') }}"
                                                data-amount="{{ number_format($creditGroup->sum('credit'), 0, ',', '.') }}">
                                                <i class="feather-check-circle me-1"></i> Verify
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
                                                    {{-- <th>Particular</th> --}}
                                                    <th>Proof</th>
                                                    <th>Status</th>
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
                    <p class="text-muted mb-3">Apakah kamu yakin ingin menandai pembayaran berikut sebagai
                        <strong>Verified</strong>?
                    </p>
                    <ul class="list-unstyled mb-3">
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
            let pastedProofBlobs = [];
            const form = document.getElementById('editPaymentForm');
            const pasteArea = document.getElementById('pasteProofArea');
            const previewContainer = document.getElementById('proofPreviewContainer');
            const fileInput = document.getElementById('payment_proof');

            // 🔹 Tombol edit
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

            // 🔹 Paste Upload
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

            pasteArea.addEventListener('paste', (e) => {

                // 🔥 Jika paste dilakukan pada input .note-input → IZINKAN paste normal
                if (e.target.classList.contains('note-input')) {
                    return;
                }

                // 📌 Selain input note, baru jalankan proses intercept screenshot
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


            fileInput.addEventListener('change', e => {
                [...e.target.files].forEach(f => {
                    pastedProofBlobs.push(f);
                    addPreview(URL.createObjectURL(f), f);
                });
            });

            // 🔹 Format angka
            const paidInput = document.getElementById('edit_paid_amount');
            paidInput.addEventListener('input', function() {
                let val = this.value.replace(/\D/g, '') || '0';
                this.value = new Intl.NumberFormat('id-ID').format(val);
            });

            // 🔹 Submit Form (AJAX)
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

                        // update tanggal
                        const dateEl = groupBox.querySelector('.bg-light span');
                        if (dateEl) {
                            dateEl.innerHTML =
                                `<strong>Tanggal:</strong> ${data.transaction_date}`;
                        }

                        // update tabel
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
                </button>
            `;
                            } else {
                                proofTd.innerHTML = `<span class="text-muted">No Proof</span>`;
                            }
                        }
                    },
                    error: x => Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: x.responseJSON?.message ?? 'Terjadi kesalahan'
                    })
                });
            });

            // 🔹 Preview Proof
            const multiProofModal = new bootstrap.Modal(document.getElementById('multiProofModal'));
            const multiProofContainer = $('#multiProofContainer');

            $(document).on('click', '.btn-preview-proof', function() {
                const proofs = JSON.parse($(this).attr('data-proofs') || '[]');
                multiProofContainer.html('');
                proofs.forEach(item => {
                    const col = $(`
                <div class="col-md-12 col-sm-12">
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

                fetch(`/erp/sales/sale-returns/verify-payment/${groupId}`, {
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

                        // ✅ update tampilan langsung
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
