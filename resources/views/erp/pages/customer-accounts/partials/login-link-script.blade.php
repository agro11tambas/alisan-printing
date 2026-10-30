<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Status dan tombol aksi (Buat/Cabut) dirender server, jadi tabel perlu
        // dimuat ulang setelah link dibuat atau dicabut.
        function reloadCustomerAccountTable() {
            if (window.jQuery && $.fn.DataTable.isDataTable('#customerAccountList')) {
                $('#customerAccountList').DataTable().ajax.reload(null, false);
            }
        }

        // Salin ulang link aktif dari daftar akun / detail customer. Delegasi ke
        // document karena tombol di tabel dirender ulang tiap DataTable reload.
        document.addEventListener('click', async function(event) {
            const button = event.target.closest('.btn-copy-login-link');

            if (!button || button.disabled) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const loginUrl = button.getAttribute('data-login-url');

            try {
                await navigator.clipboard.writeText(loginUrl);
            } catch (error) {
                const temp = document.createElement('textarea');
                temp.value = loginUrl;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                temp.remove();
            }

            const label = button.querySelector('span');
            const original = label.textContent;
            label.textContent = 'Link tersalin';
            window.setTimeout(function() {
                label.textContent = original;
            }, 1500);
        });

        const loginLinkModal = document.getElementById('modalCustomerLoginLink');
        const loginLinkForm = document.getElementById('formCustomerLoginLink');

        if (loginLinkModal && loginLinkForm) {
            const loginLinkNameHolder = document.getElementById('loginLinkCustomerName');
            const loginLinkConfirmation = document.getElementById('loginLinkConfirmation');
            const loginLinkResult = document.getElementById('loginLinkResult');
            const loginLinkUrl = document.getElementById('loginLinkUrl');
            const loginLinkMessage = document.getElementById('loginLinkMessage');
            const loginLinkError = document.getElementById('loginLinkError');
            const loginLinkExpiresInput = document.getElementById('loginLinkExpiresInHours');
            const loginLinkSingleUseInput = document.getElementById('loginLinkSingleUse');
            const generateButton = document.getElementById('generateLoginLink');
            const copyButton = document.getElementById('copyLoginLinkUrl');
            const whatsappButton = document.getElementById('sendLoginLinkWhatsapp');
            let loginLinkEndpoint = '';
            let loginLinkCustomerName = '';
            let loginLinkCustomerPhone = '';

            loginLinkModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;

                loginLinkEndpoint = button.getAttribute('data-url');
                loginLinkCustomerName = button.getAttribute('data-name') || 'Customer';
                loginLinkCustomerPhone = (button.getAttribute('data-phone') || '').replace(/\D/g, '');

                if (loginLinkCustomerPhone.startsWith('0')) {
                    loginLinkCustomerPhone = '62' + loginLinkCustomerPhone.substring(1);
                }

                loginLinkNameHolder.textContent = loginLinkCustomerName;
                loginLinkConfirmation.classList.remove('d-none');
                loginLinkResult.classList.add('d-none');
                loginLinkError.classList.add('d-none');
                loginLinkError.textContent = '';
                loginLinkUrl.value = '';
                // Default baru: tanpa kedaluwarsa dan boleh dipakai berkali-kali.
                loginLinkExpiresInput.value = '';
                loginLinkSingleUseInput.checked = false;
                whatsappButton.href = '#';
                whatsappButton.classList.toggle('d-none', !loginLinkCustomerPhone);
                generateButton.classList.remove('d-none');
                generateButton.disabled = false;
                generateButton.textContent = 'Generate Link';
            });

            loginLinkForm.addEventListener('submit', async function(event) {
                event.preventDefault();

                generateButton.disabled = true;
                generateButton.textContent = 'Memproses...';
                loginLinkError.classList.add('d-none');

                const rawExpires = loginLinkExpiresInput.value.trim();
                const expiresInHours = rawExpires === '' ? null : parseInt(rawExpires, 10);

                try {
                    const response = await fetch(loginLinkEndpoint, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': loginLinkForm.querySelector('input[name="_token"]').value,
                        },
                        body: JSON.stringify({
                            expires_in_hours: expiresInHours,
                            single_use: loginLinkSingleUseInput.checked,
                        }),
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Gagal membuat link login.');
                    }

                    loginLinkUrl.value = payload.data.login_url;
                    loginLinkMessage.textContent = payload.message;

                    const whatsappMessage = [
                        'Link Order VIP',
                        '',
                        payload.data.login_url,
                    ].join('\n');

                    if (loginLinkCustomerPhone) {
                        whatsappButton.href = 'https://wa.me/' + loginLinkCustomerPhone +
                            '?text=' + encodeURIComponent(whatsappMessage);
                    }

                    loginLinkConfirmation.classList.add('d-none');
                    loginLinkResult.classList.remove('d-none');
                    generateButton.classList.add('d-none');
                    reloadCustomerAccountTable();
                } catch (error) {
                    loginLinkError.textContent = error.message || 'Gagal membuat link login.';
                    loginLinkError.classList.remove('d-none');
                    generateButton.disabled = false;
                    generateButton.textContent = 'Generate Link';
                }
            });

            copyButton.addEventListener('click', async function() {
                try {
                    await navigator.clipboard.writeText(loginLinkUrl.value);
                } catch (error) {
                    loginLinkUrl.select();
                    document.execCommand('copy');
                }

                copyButton.innerHTML = '<i class="feather feather-check me-1"></i> Tersalin';
                window.setTimeout(function() {
                    copyButton.innerHTML = '<i class="feather feather-copy me-1"></i> Salin';
                }, 1500);
            });
        }

        const revokeModal = document.getElementById('modalRevokeCustomerLoginLink');
        const revokeForm = document.getElementById('formRevokeCustomerLoginLink');

        if (!revokeModal || !revokeForm) {
            return;
        }

        const revokeNameHolder = document.getElementById('revokeLoginLinkCustomerName');
        const revokeConfirmation = document.getElementById('revokeLoginLinkConfirmation');
        const revokeMessage = document.getElementById('revokeLoginLinkMessage');
        const revokeError = document.getElementById('revokeLoginLinkError');
        const revokeButton = document.getElementById('revokeLoginLinkButton');
        let revokeEndpoint = '';

        revokeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            revokeEndpoint = button.getAttribute('data-url');
            revokeNameHolder.textContent = button.getAttribute('data-name') || 'Customer';
            revokeConfirmation.classList.remove('d-none');
            revokeMessage.classList.add('d-none');
            revokeMessage.textContent = '';
            revokeError.classList.add('d-none');
            revokeError.textContent = '';
            revokeButton.classList.remove('d-none');
            revokeButton.disabled = false;
            revokeButton.textContent = 'Cabut Link';
        });

        revokeForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            revokeButton.disabled = true;
            revokeButton.textContent = 'Memproses...';
            revokeError.classList.add('d-none');

            try {
                const response = await fetch(revokeEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': revokeForm.querySelector('input[name="_token"]').value,
                    },
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Gagal mencabut link login.');
                }

                revokeMessage.textContent = payload.message;
                revokeMessage.classList.remove('d-none');
                revokeConfirmation.classList.add('d-none');
                revokeButton.classList.add('d-none');
                reloadCustomerAccountTable();
            } catch (error) {
                revokeError.textContent = error.message || 'Gagal mencabut link login.';
                revokeError.classList.remove('d-none');
                revokeButton.disabled = false;
                revokeButton.textContent = 'Cabut Link';
            }
        });
    });
</script>
