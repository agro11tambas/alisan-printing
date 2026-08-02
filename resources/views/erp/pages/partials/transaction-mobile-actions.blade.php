<div class="transaction-mobile-actions d-md-none">
    <a href="{{ $backUrl }}" class="btn btn-light-brand">
        <i class="feather-arrow-left"></i>
        <span>Back</span>
    </a>
    <button type="submit" class="btn btn-primary" form="{{ $formId }}">
        <i class="{{ $submitIcon ?? 'feather-save' }}"></i>
        <span>{{ $submitLabel }}</span>
    </button>
</div>
