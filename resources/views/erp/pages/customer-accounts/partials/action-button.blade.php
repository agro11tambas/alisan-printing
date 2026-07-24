<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/customer-accounts/edit/{{ $account->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>

        <li>
            <button type="button" class="dropdown-item btn-reset-password" data-bs-toggle="modal"
                data-bs-target="#modalResetCustomerPassword" data-name="{{ $account->name }}"
                data-phone="{{ $account->whatsapp_number }}"
                data-url="{{ url('/erp/customer-accounts/' . $account->id . '/password-reset-link') }}">
                <i class="feather feather-key me-3"></i>
                <span>Buat Baru/Reset Password</span>
            </button>
        </li>

        <li>
            <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                data-bs-target="#modalDeleteCustomerAccount" data-id="{{ $account->id }}"
                data-name="{{ $account->name }}" data-url="{{ url('/erp/customer-accounts/delete/' . $account->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>
    </ul>
</div>
