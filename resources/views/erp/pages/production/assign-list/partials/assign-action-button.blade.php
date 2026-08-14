<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        {{-- 🔧 Add Progress & Edit sekarang per mesin (dari halaman Assign List), di sini cuma Delete --}}
        <div class="action-grid">
            <div class="action-col">
                @if ($hasOnlyProgressStatus)
                    <li>
                        <button type="button" class="dropdown-item btn-open-delete-modal" data-id="{{ $batch->id }}"
                            data-code="{{ $batch->assign_code }}">
                            <i class="feather-trash-2 me-3"></i>
                            <span>Delete</span>
                        </button>
                    </li>
                @else
                    <li>
                        <span class="dropdown-item text-muted">
                            <i class="feather-lock me-3"></i>
                            <span>Sudah ada progress, tidak bisa dihapus</span>
                        </span>
                    </li>
                @endif
            </div>
        </div>
    </ul>
</div>
