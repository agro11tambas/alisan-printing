<script>
    (() => {
        let pendingAccountMatches = [];
        let pendingCustomerForm = null;

        const normalizePhone = value => {
            const digits = String(value || '').replace(/\D/g, '');
            if (digits.startsWith('0')) return `62${digits.slice(1)}`;
            if (digits.startsWith('8')) return `62${digits}`;
            return digits;
        };

        window.handleExistingAccountMatches = function(form) {
            const picker = document.getElementById('existing_account_picker');
            const modalElement = document.getElementById('existingAccountMatchModal');
            const matchList = document.getElementById('existingAccountMatchList');

            if (!picker || !modalElement || !matchList) return false;

            const accountsByPhone = new Map();
            picker.querySelectorAll('option[data-whatsapp]').forEach(option => {
                const phone = normalizePhone(option.dataset.whatsapp);
                if (phone && !accountsByPhone.has(phone)) {
                    accountsByPhone.set(phone, option);
                }
            });

            pendingAccountMatches = [];
            form.querySelectorAll('#accounts .account-item').forEach(row => {
                const phoneInput = row.querySelector('.phone-input');
                const phone = normalizePhone(phoneInput?.value);
                const option = accountsByPhone.get(phone);

                if (phone && option) {
                    pendingAccountMatches.push({ row, option });
                }
            });

            if (pendingAccountMatches.length === 0) return false;

            pendingCustomerForm = form;
            matchList.replaceChildren();

            const displayedAccountIds = new Set();
            pendingAccountMatches.forEach(({ option }) => {
                if (displayedAccountIds.has(option.value)) return;
                displayedAccountIds.add(option.value);

                const item = document.createElement('div');
                item.className = 'border rounded bg-light p-2';

                const name = document.createElement('div');
                name.className = 'fw-semibold';
                name.textContent = option.dataset.name || option.textContent.split(' - ')[0].trim() || '-';

                const phone = document.createElement('div');
                phone.className = 'text-muted fs-12';
                phone.textContent = option.dataset.whatsapp || '-';

                item.append(name, phone);
                matchList.appendChild(item);
            });

            bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return true;
        };

        document.getElementById('confirmUseExistingAccount')?.addEventListener('click', function() {
            if (!pendingCustomerForm || pendingAccountMatches.length === 0) return;

            const selectedAccountIds = new Set(
                Array.from(pendingCustomerForm.querySelectorAll('input[name="existing_account_ids[]"]'))
                    .map(input => input.value)
            );

            const linkedAccountIds = new Set();
            pendingAccountMatches.forEach(({ option }) => {
                if (linkedAccountIds.has(option.value) || selectedAccountIds.has(option.value)) return;
                linkedAccountIds.add(option.value);
                $('#existing_account_picker').val(option.value).trigger('change');
            });

            pendingAccountMatches.forEach(({ row }) => row.remove());
            pendingAccountMatches = [];

            if (typeof updateRemoveAccountButtons === 'function') {
                updateRemoveAccountButtons();
            }

            bootstrap.Modal.getInstance(document.getElementById('existingAccountMatchModal'))?.hide();

            const form = pendingCustomerForm;
            pendingCustomerForm = null;
            form.requestSubmit();
        });
    })();
</script>