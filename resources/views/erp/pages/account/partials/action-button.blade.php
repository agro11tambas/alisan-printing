<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/accounts/edit-account/{{ $account->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>
        <li>
            <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                data-bs-target="#modalDeleteAccount" data-id="{{ $account->id }}" data-name="{{ $account->name }}"
                data-url="{{ url('/erp/accounts/delete/' . $account->id) }}">
                <i class="feather feather-trash-2 me-3"></i>
                <span>Delete</span>
            </button>
        </li>

        <li>
            @if ($account->is_default)
                <button type="button" class="dropdown-item btn-remove-default" data-bs-toggle="modal"
                    data-bs-target="#modalRemoveDefault" data-id="{{ $account->id }}" data-name="{{ $account->name }}"
                    data-url="{{ url('/erp/accounts/remove-default/' . $account->id) }}">
                    <i class="feather feather-x-circle me-3"></i>
                    <span>Remove Default Sale</span>
                </button>
            @elseif(!$hasDefault)
                <button type="button" class="dropdown-item btn-mark-default" data-bs-toggle="modal"
                    data-bs-target="#modalMarkDefault" data-id="{{ $account->id }}" data-name="{{ $account->name }}"
                    data-url="{{ url('/erp/accounts/mark-default/' . $account->id) }}">
                    <i class="feather feather-star me-3"></i>
                    <span>Mark as Default Sale</span>
                </button>
            @endif
        </li>

        <li>
            @if ($account->is_default_purchase)
                <button type="button" class="dropdown-item btn-remove-default-purchase" data-bs-toggle="modal"
                    data-bs-target="#modalRemoveDefaultPurchase" data-id="{{ $account->id }}"
                    data-name="{{ $account->name }}"
                    data-url="{{ url('/erp/accounts/remove-default-purchase/' . $account->id) }}">
                    <i class="feather feather-x-circle me-3"></i>
                    <span>Remove Default Purchase</span>
                </button>
            @elseif(!$hasDefaultPurchase)
                <button type="button" class="dropdown-item btn-mark-default-purchase" data-bs-toggle="modal"
                    data-bs-target="#modalMarkDefaultPurchase" data-id="{{ $account->id }}"
                    data-name="{{ $account->name }}"
                    data-url="{{ url('/erp/accounts/mark-default-purchase/' . $account->id) }}">
                    <i class="feather feather-shopping-cart me-3"></i>
                    <span>Mark as Default Purchase</span>
                </button>
            @endif
        </li>

    </ul>
</div>
