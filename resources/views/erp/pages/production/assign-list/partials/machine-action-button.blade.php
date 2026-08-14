<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        <div class="action-grid">
            <div class="action-col">
                <li>
                    <a class="dropdown-item"
                        href="/erp/productions/assign-list/machine/{{ $machineKey }}/add-progress">
                        <i class="feather feather-plus me-3"></i>
                        <span>Add Progress</span>
                    </a>
                </li>
            </div>
            <div class="action-col">
                <li>
                    <a class="dropdown-item" href="/erp/productions/assign-list/machine/{{ $machineKey }}/edit">
                        <i class="feather-edit me-3"></i>
                        <span>Edit</span>
                    </a>
                </li>
            </div>
            <div class="action-col">
                <li>
                    <a class="dropdown-item"
                        href="/erp/productions/assign-list/machine/{{ $machineKey }}/print{{ $query }}"
                        target="_blank">
                        <i class="feather-printer me-3"></i>
                        <span>Print</span>
                    </a>
                </li>
            </div>
            <div class="action-col">
                <li>
                    <a class="dropdown-item"
                        href="/erp/productions/assign-list/machine/{{ $machineKey }}/detail{{ $query }}">
                        <i class="feather-list me-3"></i>
                        <span>Detail</span>
                    </a>
                </li>
            </div>
        </div>
    </ul>
</div>
