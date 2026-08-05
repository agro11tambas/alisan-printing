/* Tabel read-only jadi kartu di mobile: judul di atas, lalu label kiri / nilai kanan.
   Pakai class .table-mobile-cards pada <table> dan data-label pada tiap <td>. */
@media (max-width: 767.98px) {

    .table-mobile-cards {
        margin-bottom: 0;
        border: 0;
    }

    .table-mobile-cards thead {
        display: none !important;
    }

    .table-mobile-cards,
    .table-mobile-cards tbody {
        display: block;
        width: 100%;
    }

    .table-mobile-cards > tbody > tr {
        display: block;
        margin-bottom: 10px;
        padding: 12px;
        border: 1px solid #dfe6ee;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
    }

    .table-mobile-cards > tbody > tr:last-child {
        margin-bottom: 0;
    }

    .table-mobile-cards > tbody > tr > td {
        display: grid;
        grid-template-columns: minmax(0, 40%) minmax(0, 1fr);
        gap: 10px;
        align-items: baseline;
        min-width: 0;
        padding: 5px 0 !important;
        border: 0 !important;
        background: transparent !important;
        font-size: 14px;
        overflow-wrap: anywhere;
    }

    .table-mobile-cards > tbody > tr > td + td {
        border-top: 1px dashed #eef2f7 !important;
    }

    .table-mobile-cards > tbody > tr > td::before {
        content: attr(data-label);
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.35;
    }

    .table-mobile-cards > tbody > tr > td[colspan] {
        display: block;
        padding: 6px 0 !important;
    }

    .table-mobile-cards > tbody > tr > td[colspan]::before {
        content: none;
    }

    .table-mobile-cards .mobile-card-title {
        display: block !important;
        color: #1e293b;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.3;
    }

    .table-mobile-cards .mobile-card-title::before {
        content: none;
    }

    /* Kartu tidak butuh scroll horizontal. */
    .table-responsive:has(> .table-mobile-cards) {
        overflow-x: visible;
    }
}
