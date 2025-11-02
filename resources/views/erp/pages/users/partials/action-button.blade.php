<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <li>
            <a class="dropdown-item" href="/erp/shop-manager/edit-user/{{ $user->id }}">
                <i class="feather feather-edit-3 me-3"></i>
                <span>Edit</span>
            </a>
        </li>

        {{-- 🔒 Hanya tampilkan tombol Delete jika bukan Owner --}}
        @if ($user->role !== 'Owner')
            <li>
                <button type="button" class="dropdown-item btn-delete" data-bs-toggle="modal"
                    data-bs-target="#modalDeleteShopManager" data-id="{{ $user->id }}" data-name="{{ $user->name }}"
                    data-url="{{ url('/erp/shop-manager/delete/' . $user->id) }}">
                    <i class="feather feather-trash-2 me-3"></i>
                    <span>Delete</span>
                </button>
            </li>
        @else
            <li>
                <button type="button" class="dropdown-item text-muted" disabled title="Owner tidak dapat dihapus">
                    <i class="feather feather-lock me-3"></i>
                    <span>Protected (Owner)</span>
                </button>
            </li>
        @endif
    </ul>
</div>
