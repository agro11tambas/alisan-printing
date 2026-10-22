<script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginLinkModal = document.getElementById('modalCustomerLoginLink');
        const loginLinkForm = document.getElementById('formCustomerLoginLink');

        if (!loginLinkModal || !loginLinkForm) {
            return;
        }

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
            loginLinkExpiresInput.value = '24';
            loginLinkSingleUseInput.checked = true;
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

            const expiresInHours = parseInt(loginLinkExpiresInput.value, 10) || 24;

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
                    'Halo ' + loginLinkCustomerName + ',',
                    '',
                    'Silakan masuk ke akun Anda melalui link berikut:',
                    payload.data.login_url,
                    '',
                    'Link berlaku selama ' + expiresInHours + ' jam dan hanya untuk Anda.',
                ].join('\n');

                if (loginLinkCustomerPhone) {
                    whatsappButton.href = 'https://wa.me/' + loginLinkCustomerPhone +
                        '?text=' + encodeURIComponent(whatsappMessage);
                }

                loginLinkConfirmation.classList.add('d-none');
                loginLinkResult.classList.remove('d-none');
                generateButton.classList.add('d-none');
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
    });
</script>
