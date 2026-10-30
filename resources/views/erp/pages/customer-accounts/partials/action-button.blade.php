<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/customer-accounts/edit/{{ $account->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>

        @php
            // Link yang masih bisa dipakai harus dicabut dulu sebelum membuat yang baru.
            $hasUsableLoginLink = $account->hasUsableLoginLink();
        @endphp

        <li>
            <button type="button" class="dropdown-item btn-login-link" data-bs-toggle="modal"
                data-bs-target="#modalCustomerLoginLink" data-name="{{ $account->name }}"
                data-phone="{{ $account->whatsapp_number }}"
                data-url="{{ url('/erp/customer-accounts/' . $account->id . '/login-link') }}"
                @if ($hasUsableLoginLink) disabled title="Cabut link login aktif dulu sebelum membuat yang baru" @endif>
                <i class="feather feather-link me-3"></i>
                <span>Buat Link Login</span>
            </button>
        </li>

        <li>
            <button type="button" class="dropdown-item btn-revoke-login-link" data-bs-toggle="modal"
                data-bs-target="#modalRevokeCustomerLoginLink" data-name="{{ $account->name }}"
                data-url="{{ url('/erp/customer-accounts/' . $account->id . '/login-link/revoke') }}"
                @unless ($account->hasUnrevokedLoginLink()) disabled title="Tidak ada link login untuk dicabut" @endunless>
                <i class="feather feather-slash me-3"></i>
                <span>Cabut Link Login</span>
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
