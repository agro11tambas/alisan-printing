<div class="modal fade" id="modalCustomerLoginLink" tabindex="-1"
    aria-labelledby="loginLinkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formCustomerLoginLink">
            @csrf

            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white" id="loginLinkModalLabel">Buat Link Login Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="loginLinkConfirmation">
                        <p>
                            Buat link login langsung untuk
                            <strong id="loginLinkCustomerName"></strong>?
                        </p>

                        <div class="mb-3">
                            <label for="loginLinkExpiresInHours" class="form-label fw-semibold">Masa Berlaku (jam)</label>
                            <input type="number" id="loginLinkExpiresInHours" class="form-control" value="24" min="1"
                                max="168">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="loginLinkSingleUse" checked>
                            <label class="form-check-label" for="loginLinkSingleUse">
                                Sekali pakai
                            </label>
                        </div>

                        <p class="text-muted mb-0">
                            Link ini setara password: siapa pun yang membukanya langsung masuk sebagai customer
                            tersebut. Kirim hanya ke nomor WhatsApp customer yang bersangkutan.
                        </p>
                    </div>

                    <div id="loginLinkResult" class="d-none">
                        <label for="loginLinkUrl" class="form-label fw-semibold">Link Login</label>
                        <div class="input-group">
                            <input type="text" id="loginLinkUrl" class="form-control" readonly>
                            <button type="button" class="btn btn-outline-primary" id="copyLoginLinkUrl">
                                <i class="feather feather-copy me-1"></i> Salin
                            </button>
                        </div>
                        <a href="#" class="btn btn-success mt-3" id="sendLoginLinkWhatsapp"
                            target="_blank" rel="noopener noreferrer">
                            <i class="feather feather-message-circle me-1"></i>
                            Kirim via WhatsApp
                        </a>
                        <p class="text-success mt-3 mb-0" id="loginLinkMessage"></p>
                    </div>

                    <div class="alert alert-danger d-none mt-3 mb-0" id="loginLinkError"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-md" id="generateLoginLink">
                        Generate Link
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
