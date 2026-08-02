function appendSalesMetaLine(wrapper, className, value) {
    const text = String(value || '').trim();
    if (!text) return;

    $('<span>', { class: className, text }).appendTo(wrapper);
}

function getSalesBusinessMeta(data) {
    const option = data?.element;

    return {
        name: option?.dataset?.businessName || data?.text || '',
    };
}

function buildSalesBusinessTemplate(data, selected = false) {
    if (!data.id) return data.text;

    const meta = getSalesBusinessMeta(data);
    const wrapper = $('<span>', {
        class: selected ? 'sales-business-selection' : 'sales-business-option'
    });

    appendSalesMetaLine(wrapper, 'sales-business-option__name', meta.name);

    return wrapper;
}

function getSalesAddressMeta(data) {
    const option = data?.element;

    return {
        business: option?.dataset?.business || '',
        address: option?.dataset?.address || '',
        map: option?.dataset?.map || '',
    };
}

function buildSalesAddressTemplate(data, selected = false) {
    if (!data.id) return data.text;

    const meta = getSalesAddressMeta(data);
    const wrapper = $('<span>', {
        class: selected ? 'sales-address-selection' : 'sales-address-option'
    });

    appendSalesMetaLine(wrapper, 'sales-address-option__business', meta.business);
    appendSalesMetaLine(wrapper, 'sales-address-option__text', meta.address);

    return wrapper;
}

function getSalesContactMeta(data) {
    const option = data?.element;

    return {
        name: option?.dataset?.contactName || '',
        contact: option?.dataset?.contactDetail || '',
    };
}

function buildSalesContactTemplate(data, selected = false) {
    if (!data.id) return data.text;

    const meta = getSalesContactMeta(data);
    const wrapper = $('<span>', {
        class: selected ? 'sales-contact-selection' : 'sales-contact-option'
    });

    appendSalesMetaLine(wrapper, 'sales-contact-option__name', meta.name);
    appendSalesMetaLine(wrapper, 'sales-contact-option__text', meta.contact);

    return wrapper;
}
function initSalesCustomerSelects() {
    const businessSelect = $('#customers');
    const addressSelect = $('#addresses');
    const contactSelect = $('#customer_accounts');

    [businessSelect, addressSelect, contactSelect].forEach(select => {
        if (select.hasClass('select2-hidden-accessible')) {
            select.select2('destroy');
        }
    });

    businessSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Choose Business',
        templateResult: data => buildSalesBusinessTemplate(data, false),
        templateSelection: data => buildSalesBusinessTemplate(data, true)
    });

    addressSelect.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Choose Address',
        templateResult: data => buildSalesAddressTemplate(data, false),
        templateSelection: data => buildSalesAddressTemplate(data, true)
    });

    contactSelect.select2({
        theme: 'bootstrap-5',
        placeholder: 'Choose Contact',
        templateResult: data => buildSalesContactTemplate(data, false),
        templateSelection: data => buildSalesContactTemplate(data, true)
    });
}
function resetSalesAddressOptions(addresses) {
    const select = document.getElementById('addresses');
    if (!select) return;

    select.innerHTML = '';
    const placeholder = new Option('Choose Address', '', true, true);
    placeholder.disabled = true;
    placeholder.hidden = true;
    select.appendChild(placeholder);

    addresses.forEach(address => {
        const business = String(address.business_name || '').trim();
        const addressText = String(address.address || '').trim();
        const optionText = [business, addressText].filter(Boolean).join(' - ');
        const option = new Option(optionText, address.id, false, false);

        option.dataset.business = business;
        option.dataset.address = addressText;
        option.dataset.map = address.google_maps || '';
        select.appendChild(option);
    });

    $('#addresses').val(null).trigger('change.select2');
    $('#google-maps-link').empty();
}

function resetSalesContactOptions(accounts) {
    const select = document.getElementById('customer_accounts');
    if (!select) return;

    select.innerHTML = '';
    const placeholder = new Option('Choose Contact', '', true, true);
    placeholder.disabled = true;
    placeholder.hidden = true;
    select.appendChild(placeholder);

    accounts.forEach(account => {
        const name = String(account.name || '').trim();
        const contact = String(account.whatsapp_number || '').trim();
        const optionText = [name, contact].filter(Boolean).join(' - ');
        const option = new Option(optionText, account.id, false, false);

        option.dataset.contactName = name;
        option.dataset.contactDetail = contact;
        select.appendChild(option);
    });

    $('#customer_accounts').val(null).trigger('change.select2');
}

function renderSelectedSalesAddressMap() {
    const selected = document.querySelector('#addresses option:checked');
    const mapsContainer = document.getElementById('google-maps-link');
    if (!mapsContainer) return;

    mapsContainer.replaceChildren();
    if (!selected?.value) return;

    const mapUrl = selected.dataset.map || '';
    if (!/^https?:\/\//i.test(mapUrl)) return;

    const link = document.createElement('a');
    link.href = mapUrl;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.className = 'btn btn-sm btn-outline-primary mt-1';
    link.textContent = 'Lihat di Google Maps';
    mapsContainer.appendChild(link);
}
