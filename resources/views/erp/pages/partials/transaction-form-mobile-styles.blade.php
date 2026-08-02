/* Shared mobile treatment for Sale/Purchase create and edit forms. */
@media (max-width: 767.98px) {
    .page-header { min-height: 60px; padding: 10px 12px; }
    .page-header-left { min-width: 0; }
    .page-header-title { padding-right: 0; margin-right: 0; border-right: 0; }
    .page-header-title h5 { margin: 0 !important; font-size: 17px; line-height: 1.25; }
    .page-header-left .breadcrumb,
    .page-header-right-open-toggle,
    .page-header-right-close-toggle { display: none !important; }

    /* The theme turns header actions into an off-canvas drawer on mobile. */
    .page-header .page-header-right { display: none !important; }

    .page-header-right .page-header-right-items {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        transform: none !important;
        position: fixed !important;
        inset: auto 0 0 0 !important;
        width: auto !important;
        height: auto !important;
        padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
        background: #fff;
        border-top: 1px solid #e5e7eb;
        box-shadow: 0 -8px 24px rgba(15, 23, 42, .10);
        z-index: 1040;
    }

    .page-header-right-items-wrapper {
        display: grid !important;
        grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
        gap: 8px !important;
        width: 100%;
    }

    .page-header-right-items-wrapper .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 0;
        min-height: 44px;
        padding: 9px 10px;
        white-space: nowrap;
    }

    .page-header-right-items-wrapper .btn i { margin-right: 6px !important; }

    .transaction-form-page {
        padding-bottom: calc(78px + env(safe-area-inset-bottom)) !important;
        overflow-x: clip;
    }

    .transaction-form-page > .row { margin-right: 0; margin-left: 0; }
    .transaction-form-page > .row > [class*="col-"] { padding-right: 0; padding-left: 0; }

    .transaction-form-page .card {
        margin-bottom: 10px;
        border: 1px solid #e8edf3;
        border-radius: 12px;
        box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
    }

    .transaction-form-page .card-body { padding: 12px !important; }
    .transaction-form-page .card-body .row.mb-2 { margin-bottom: 12px !important; }

    .transaction-form-page .card-body .row.mb-2 > [class*="col-lg-2"] > label {
        display: block;
        margin-bottom: 6px;
        color: #475569;
        font-size: 13px;
        line-height: 1.35;
    }

    .transaction-form-page input.form-control,
    .transaction-form-page select.form-control,
    .transaction-form-page select.form-select,
    .transaction-form-page textarea.form-control {
        min-height: 44px;
        font-size: 16px !important;
    }

    .transaction-form-page textarea.form-control { min-height: 82px; }
    .transaction-form-page .select2-container { width: 100% !important; max-width: 100%; }

    .transaction-form-page .select2-container--bootstrap-5 .select2-selection--single {
        height: 44px !important;
        padding: 5px 34px 5px 10px !important;
    }

    .transaction-form-page .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding: 0 !important;
        font-size: 16px !important;
        line-height: 32px !important;
    }

    .transaction-form-page .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
        display: block !important;
        height: 42px !important;
        right: 8px !important;
    }

    .transaction-form-page h5.fw-bold { margin: 4px 0 10px; font-size: 16px; }
    .transaction-form-page .product-grid-header { display: none !important; }

    .transaction-form-page .product-item {
        margin-bottom: 10px !important;
        padding: 12px !important;
        border: 1px solid #dfe6ee;
        border-radius: 12px !important;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
    }

    .transaction-form-page .product-item .product-grid {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 10px !important;
        min-width: 0;
    }

    .transaction-form-page .product-item .product-col-span-2,
    .transaction-form-page .product-item .product-bundle-inline,
    .transaction-form-page .product-item .bundle-wrapper {
        grid-column: 1 / -1 !important;
        min-width: 0;
    }

    .transaction-form-page .product-item .form-group { min-width: 0; margin-bottom: 0; }

    .transaction-form-page .product-item .form-group > label,
    .transaction-form-page .product-item .product-delete-col > label {
        display: flex !important;
        align-items: center;
        height: auto !important;
        min-height: 18px;
        margin-bottom: 5px !important;
        color: #64748b;
        font-size: 12px !important;
        font-weight: 600;
        line-height: 1.2;
    }

    .transaction-form-page .product-item .form-control,
    .transaction-form-page .product-item .select2-container--bootstrap-5 .select2-selection--single {
        height: 42px !important;
        min-height: 42px !important;
    }

    .transaction-form-page .product-item .form-control {
        padding: 7px 9px !important;
        font-size: 15px !important;
    }

    .transaction-form-page .product-bundle-inline,
    .transaction-form-page .product-bundle-inline:has(.bundle-wrapper:not(.d-none)) {
        grid-template-columns: 1fr !important;
        gap: 8px;
    }

    .transaction-form-page .bundle-toggle span {
        justify-content: center;
        width: 100%;
        min-height: 40px;
    }

    .transaction-form-page .product-delete-col {
        grid-column: 1 / -1;
        display: block;
        min-width: 0;
    }

    .transaction-form-page .product-delete-col > label { display: none !important; }

    .transaction-form-page .product-delete-col .delete-row {
        width: 100% !important;
        height: 40px !important;
        min-height: 40px;
        border-radius: 8px;
    }

    .transaction-form-page #add_row { width: 100%; min-height: 44px; }
    .transaction-form-page #tab_logic_total { margin-bottom: 0; }
    .transaction-form-page #tab_logic_total td { padding: 7px; vertical-align: middle; }
    .transaction-mobile-actions {
        position: fixed;
        inset: auto 0 0 0;
        z-index: 1050;
        display: grid !important;
        grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr);
        gap: 8px;
        padding: 10px 12px calc(10px + env(safe-area-inset-bottom));
        background: #fff;
        border-top: 1px solid #e5e7eb;
        box-shadow: 0 -8px 24px rgba(15, 23, 42, .10);
    }

    .transaction-mobile-actions .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 0;
        min-height: 44px;
        padding: 9px 10px;
        white-space: nowrap;
    }

    .transaction-mobile-actions .btn i { margin-right: 6px; }

    .transaction-form-page #tab_logic_total input.form-control {
        min-width: 0;
        height: 42px !important;
        padding: 7px 9px;
        font-size: 15px !important;
    }
}

@media (max-width: 389.98px) {
    .page-header-right-items-wrapper .btn { font-size: 12px; }
    .page-header-right-items-wrapper .btn i { display: none; }
    .transaction-mobile-actions .btn { font-size: 12px; }
    .transaction-mobile-actions .btn i { display: none; }
    .transaction-form-page .product-item { padding: 10px !important; }
    .transaction-form-page .product-item .product-grid { gap: 8px !important; }
}
/* Sales customer fields: Address may grow, Business and Contact keep standard field size. */
.sales-business-field .select2-selection__rendered,
.sales-contact-field .select2-selection__rendered,
.sales-address-field .select2-selection__rendered {
    font-weight: 400 !important;
}
.sales-address-field .select2-container {
    width: 100% !important;
}

.sales-address-field .select2-container--bootstrap-5 .select2-selection--single {
    height: auto !important;
    min-height: 52px !important;
    padding: 6px 34px 6px 10px !important;
}

.sales-address-field .select2-selection__rendered {
    overflow: visible !important;
    padding: 0 !important;
    line-height: 1.3 !important;
    text-overflow: clip !important;
    white-space: normal !important;
}

.sales-business-option,
.sales-business-selection,
.sales-address-option,
.sales-address-selection,
.sales-contact-option,
.sales-contact-selection {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 2px;
    padding: 3px 0;
    white-space: normal;
}

.sales-business-option__name,
.sales-address-option__business,
.sales-contact-option__name {
    color: #1e293b;
    font-size: 13px;
    font-weight: 400;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.sales-business-option__text,
.sales-address-option__text,
.sales-contact-option__text {
    color: #64748b;
    font-size: 12px;
    line-height: 1.4;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.sales-business-selection,
.sales-contact-selection {
    flex-direction: row;
    align-items: center;
    gap: 8px;
    padding: 0;
    overflow: hidden;
    white-space: nowrap;
}

.sales-business-selection .sales-business-option__name,
.sales-business-selection .sales-business-option__text,
.sales-contact-selection .sales-contact-option__name,
.sales-contact-selection .sales-contact-option__text {
    min-width: 0;
    overflow: hidden;
    line-height: inherit;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (min-width: 768px) {
    .sales-address-field .select2-container--bootstrap-5 .select2-selection--single {
        min-height: 64px !important;
        padding: 9px 38px 9px 12px !important;
    }

    .sales-business-option,
    .sales-business-selection,
    .sales-address-option,
    .sales-address-selection,
    .sales-contact-option,
    .sales-contact-selection {
        gap: 4px;
        padding: 4px 0;
    }

    .sales-address-option__business {
        font-size: 14px;
        line-height: 1.3;
    }

    .sales-business-option__name,
    .sales-contact-option__name {
        font-size: 14px;
        line-height: 1.2;
    }

    .sales-address-option__text {
        font-size: 13px;
        line-height: 1.45;
    }

    .sales-business-option__text,
    .sales-contact-option__text {
        font-size: 13px;
        line-height: 1.3;
    }

    .sales-business-selection,
    .sales-contact-selection {
        padding: 0;
    }
}

@media (max-width: 767.98px) {
    .transaction-form-page .sales-address-field .select2-container--bootstrap-5 .select2-selection--single {
        height: auto !important;
        min-height: 58px !important;
        padding: 7px 34px 7px 10px !important;
    }

    .transaction-form-page .sales-address-field .select2-selection__rendered {
        font-size: 14px !important;
        line-height: 1.35 !important;
    }
}