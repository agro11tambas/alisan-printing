<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        {{-- <li>
            <a class="dropdown-item" href="/erp/productions/waiting-list/add-progress-order/{{ $progress->id }}">
                <i class="feather feather-plus me-3"></i>
                <span>Add Progress</span>
            </a>
        </li> --}}
        @unless ($allCompleted)
            <li>
                <a class="dropdown-item" href="/erp/productions/waiting-list/add-assign/{{ $progress->id }}">
                    <i class="feather feather-user-plus me-3"></i>
                    <span>Add Assign</span>
                </a>
            </li>
        @endunless
        <li>
            <a class="dropdown-item" href="/erp/productions/waiting-list/history-order/{{ $progress->id }}">
                <i class="feather feather-info me-3"></i>
                <span>Progress & Info</span>
            </a>
        </li>
    </ul>
</div>
