<div class="modal fade" id="existingAccountMatchModal" tabindex="-1" aria-labelledby="existingAccountMatchModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="existingAccountMatchModalLabel">Customer Account Sudah Ada</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">
                    Nomor WhatsApp yang dimasukkan sudah terdaftar. Customer Account berikut akan langsung dipilih:
                </p>
                <div id="existingAccountMatchList" class="d-flex flex-column gap-2"></div>
                <p class="text-muted fs-12 mt-3 mb-0">
                    Klik Lanjutkan untuk menggunakan account existing tersebut dan menyimpan customer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="confirmUseExistingAccount">
                    Pilih Account &amp; Lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>