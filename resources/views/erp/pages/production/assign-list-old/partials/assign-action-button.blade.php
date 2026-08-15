{{-- 🔧 Action button versi lama: Add Progress & Edit masih per batch (invoice), bukan per mesin. --}}
<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @unless ($allCompleted)
            <div class="action-grid">
                <div class="action-col">
                    <li>
                        <a class="dropdown-item" href="/erp/productions/assign-list/add-progress/{{ $batch->id }}">
                            <i class="feather feather-plus me-3"></i>
                            <span>Add Progress</span>
                        </a>
                    </li>
                </div>
                <div class="action-col">
                    <li>
                        <a class="dropdown-item" href="/erp/productions/assign-list/edit-assign/{{ $batch->id }}">
                            <i class="feather-edit me-3"></i>
                            <span>Edit</span>
                        </a>
                    </li>
                </div>
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
        @else
            <div class="action-grid">
                <div class="action-col">
                    <li>
                        <span class="dropdown-item text-muted">
                            <i class="feather-check-circle me-3"></i>
                            <span>Assign sudah selesai</span>
                        </span>
                    </li>
                </div>
            </div>
        @endunless
    </ul>
</div>
