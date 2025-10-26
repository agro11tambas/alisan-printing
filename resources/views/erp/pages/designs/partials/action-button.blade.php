<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @if ($design->status !== 'Verified' && $allUploaded)
            <li>
                <button type="button" class="dropdown-item btn-verify" data-bs-toggle="modal"
                    data-bs-target="#verifyDesignModal" data-id="{{ $design->id }}"
                    data-name="{{ $design->design_number ?? 'Design #' . $design->id }}"
                    data-url="{{ route('design.verify', $design->id) }}">
                    <i class="feather feather-check me-3"></i>
                    <span>Verified</span>
                </button>
            </li>
        @endif
    </ul>
</div>
