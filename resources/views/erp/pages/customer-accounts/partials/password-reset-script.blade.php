<script>
    document.addEventListener('DOMContentLoaded', function() {
        const resetModal = document.getElementById('modalResetCustomerPassword');
        const resetForm = document.getElementById('formResetCustomerPassword');

        if (!resetModal || !resetForm) {
            return;
        }

        const resetNameHolder = document.getElementById('resetPasswordCustomerName');
        const resetConfirmation = document.getElementById('resetPasswordConfirmation');
        const resetResult = document.getElementById('resetPasswordResult');
        const resetUrl = document.getElementById('resetPasswordUrl');
        const resetMessage = document.getElementById('resetPasswordMessage');
        const resetError = document.getElementById('resetPasswordError');
        const generateButton = document.getElementById('generateResetPasswordLink');
        const copyButton = document.getElementById('copyResetPasswordUrl');
        const whatsappButton = document.getElementById('sendResetPasswordWhatsapp');
        let resetEndpoint = '';
        let resetCustomerName = '';
        let resetCustomerPhone = '';

        resetModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;

            resetEndpoint = button.getAttribute('data-url');
            resetCustomerName = button.getAttribute('data-name') || 'Customer';
            resetCustomerPhone = (button.getAttribute('data-phone') || '').replace(/\D/g, '');

            if (resetCustomerPhone.startsWith('0')) {
                resetCustomerPhone = '62' + resetCustomerPhone.substring(1);
            }

            resetNameHolder.textContent = resetCustomerName;
            resetConfirmation.classList.remove('d-none');
            resetResult.classList.add('d-none');
            resetError.classList.add('d-none');
            resetError.textContent = '';
            resetUrl.value = '';
            whatsappButton.href = '#';
            whatsappButton.classList.toggle('d-none', !resetCustomerPhone);
            generateButton.classList.remove('d-none');
            generateButton.disabled = false;
            generateButton.textContent = 'Generate Link';
        });

        resetForm.addEventListener('submit', async function(event) {
            event.preventDefault();

            generateButton.disabled = true;
            generateButton.textContent = 'Memproses...';
            resetError.classList.add('d-none');

            try {
                const response = await fetch(resetEndpoint, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': resetForm.querySelector('input[name="_token"]').value,
                    },
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.message || 'Gagal membuat link buat baru/reset password.');
                }

                resetUrl.value = payload.data.reset_url;
                resetMessage.textContent = payload.message;
                const whatsappMessage = [
                    `Halo ${resetCustomerName},`,
                    '',
                    'Silakan buat baru/reset password akun Anda melalui link berikut:',
                    payload.data.reset_url,
                    '',
                    'Link berlaku selama 24 jam.',
                ].join('\n');
                if (resetCustomerPhone) {
                    whatsappButton.href =
                        `https://wa.me/${resetCustomerPhone}?text=${encodeURIComponent(whatsappMessage)}`;
                }
                resetConfirmation.classList.add('d-none');
                resetResult.classList.remove('d-none');
                generateButton.classList.add('d-none');

                if (window.jQuery && $.fn.DataTable &&
                    $.fn.DataTable.isDataTable('#customerAccountList')) {
                    $('#customerAccountList').DataTable().ajax.reload(null, false);
                }
            } catch (error) {
                resetError.textContent = error.message || 'Gagal membuat link buat baru/reset password.';
                resetError.classList.remove('d-none');
                generateButton.disabled = false;
                generateButton.textContent = 'Generate Link';
            }
        });

        copyButton.addEventListener('click', async function() {
            try {
                await navigator.clipboard.writeText(resetUrl.value);
            } catch (error) {
                resetUrl.select();
                document.execCommand('copy');
            }

            copyButton.innerHTML = '<i class="feather feather-check me-1"></i> Tersalin';
            window.setTimeout(function() {
                copyButton.innerHTML = '<i class="feather feather-copy me-1"></i> Salin';
            }, 1500);
        });
    });
</script>
