<div class="dropdown">
    <ul class="dropdown-menu show static-action-menu">
        @if ($design->status !== 'Verified')
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
        {{-- @if ($design->status !== 'Verified' && $allUploaded)
        @endif --}}
        {{-- @if ($design->status === 'Verified')
            <li>
                <button type="button" class="dropdown-item btn-unverify" data-bs-toggle="modal"
                    data-bs-target="#unverifyDesignModal" data-id="{{ $design->id }}"
                    data-name="{{ $design->design_number ?? 'Design #' . $design->id }}"
                    data-url="{{ route('design.unverify', $design->id) }}">
                    <i class="feather feather-x-circle me-3"></i>
                    <span>Batal Verified</span>
                </button>
            </li>
        @endif --}}
    </ul>
</div>
