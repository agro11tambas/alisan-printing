<div class="modal fade" id="modalResetCustomerPassword" tabindex="-1"
    aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formResetCustomerPassword">
            @csrf

            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="resetPasswordModalLabel">Buat Baru/Reset Password Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="resetPasswordConfirmation">
                        <p>
                            Buat link buat baru/reset password untuk
                            <strong id="resetPasswordCustomerName"></strong>?
                        </p>
                        <p class="text-muted mb-0">
                            Link hanya berlaku selama 30 menit. Pembuatan link baru akan membatalkan link sebelumnya.
                        </p>
                    </div>

                    <div id="resetPasswordResult" class="d-none">
                        <label for="resetPasswordUrl" class="form-label fw-semibold">Link Website</label>
                        <div class="input-group">
                            <input type="text" id="resetPasswordUrl" class="form-control" readonly>
                            <button type="button" class="btn btn-outline-primary" id="copyResetPasswordUrl">
                                <i class="feather feather-copy me-1"></i> Salin
                            </button>
                        </div>
                        <a href="#" class="btn btn-success mt-3" id="sendResetPasswordWhatsapp"
                            target="_blank" rel="noopener noreferrer">
                            <i class="feather feather-message-circle me-1"></i>
                            Kirim via WhatsApp
                        </a>
                        <p class="text-success mt-3 mb-0" id="resetPasswordMessage"></p>
                    </div>

                    <div class="alert alert-danger d-none mt-3 mb-0" id="resetPasswordError"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-md" id="generateResetPasswordLink">
                        Generate Link
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
