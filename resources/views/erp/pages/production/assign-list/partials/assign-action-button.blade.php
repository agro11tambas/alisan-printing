<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @unless ($allCompleted)
            <li>
                <a class="dropdown-item" href="/erp/productions/assign-list/add-progress/{{ $batch->id }}">
                    <i class="feather feather-plus me-3"></i>
                    <span>Add Progress</span>
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="/erp/productions/assign-list/edit-assign/{{ $batch->id }}">
                    <i class="feather-edit"></i>
                    <span>Edit</span>
                </a>
            </li>
        @endunless
    </ul>
</div>
