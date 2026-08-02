/* Direct, one-tap header action for transaction list pages on mobile. */
@media (max-width: 767.98px) {
    .page-header {
        min-height: 60px;
        padding: 9px 12px;
        gap: 10px;
    }

    .page-header-left {
        min-width: 0;
        flex: 1 1 auto;
    }

    .page-header-title {
        min-width: 0;
        padding-right: 0;
        margin-right: 0;
        border-right: 0;
    }

    .page-header-title h5 {
        overflow: hidden;
        margin: 0 !important;
        font-size: 17px;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .page-header-left .breadcrumb,
    .transaction-list-desktop-actions,
    .page-header-right-open-toggle {
        display: none !important;
    }

    .page-header-right {
        display: flex !important;
        flex: 0 0 auto;
        align-items: center;
    }

    .transaction-list-mobile-action {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        min-height: 42px;
        padding: 8px 12px;
        border-radius: 9px;
        font-size: 13px;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
        touch-action: manipulation;
        transition: none !important;
        -webkit-tap-highlight-color: transparent;
    }

    .transaction-list-mobile-action i {
        margin-right: 6px;
        font-size: 17px;
    }
}

@media (max-width: 389.98px) {
    .transaction-list-mobile-action {
        width: 42px;
        min-width: 42px;
        padding: 8px;
    }

    .transaction-list-mobile-action i {
        margin-right: 0;
    }

    .transaction-list-mobile-action span {
        display: none;
    }
}
