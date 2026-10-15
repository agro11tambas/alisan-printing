<?php

namespace App\Exports;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrderExport extends BaseExcelExport
{
    private const CHUNK_SIZE = 200;

    private const HEADERS = [
        'Purchase Number',
        'Purchase Date',
        'Supplier',
        'PO Status',
        'Purchase List',
        'PL Date',
        'PL Payment',
        'Nama Product',
        'SKU',
        'Qty',
        'Unit',
        'Unit Price',
        'Total Amount',
        'Stock In',
        'Total Purchase List',
        'Total Purchase Order',
    ];

    /** Kolom milik header PO, di-merge sepanjang seluruh blok PO. */
    private const PO_COLUMNS = ['A', 'B', 'C', 'D', 'P'];

    /** Kolom milik header Purchase List, di-merge sepanjang blok PL. */
    private const PL_COLUMNS = ['E', 'F', 'G', 'O'];

    /** Kolom angka rupiah (1 = A). */
    private const CURRENCY_COLUMNS = [12, 13, 15, 16];

    /** Kolom teks panjang yang rata kiri: Supplier dan Nama Product. */
    private const TEXT_COLUMNS = [3, 8];

    /** Kolom kuantitas. */
    private const QTY_COLUMNS = ['J', 'N'];

    public function __construct(private Builder $query)
    {
        parent::__construct();
    }

    protected function build(): void
    {
        $sheet = $this->makeSheet('Purchase Order', true);

        $this->writeHeader($sheet, self::HEADERS, false);
        $this->styleColumnGroups($sheet);

        $row = 1;

        $this->query
            ->with([
                'supplier:id,name',
                'purchaseLists' => fn ($query) => $query
                    ->where('status', 'Purchase List')
                    ->orderBy('purchase_date')
                    ->orderBy('id'),
                'purchaseLists.purchaseItems.purchaseProduct:id,name,sku',
                'purchaseLists.purchaseItems.inventoryItems',
            ])
            ->chunk(self::CHUNK_SIZE, function ($purchaseOrders) use ($sheet, &$row) {
                foreach ($purchaseOrders as $purchaseOrder) {
                    $row = $this->writePurchaseOrder($sheet, $purchaseOrder, $row);
                }
            });

        $lastRow = $this->writeTotalRow($sheet, $row);
        $this->finalizeSheet($sheet, count(self::HEADERS), $lastRow, self::CURRENCY_COLUMNS, self::TEXT_COLUMNS);

        if ($row >= 2) {
            foreach (self::QTY_COLUMNS as $column) {
                $sheet->getStyle($column.'2:'.$column.$row)
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }
        }

        // Auto size mengabaikan isi merge cell, jadi kolom header diberi lebar tetap.
        $fixedWidths = ['A' => 20, 'B' => 17, 'C' => 22, 'D' => 12, 'E' => 20, 'F' => 17, 'G' => 13, 'O' => 19, 'P' => 20];

        foreach ($fixedWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setAutoSize(false);
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    /**
     * Tulis satu PO beserta seluruh purchase list turunannya.
     *
     * @return int nomor baris terakhir yang terpakai
     */
    private function writePurchaseOrder(Worksheet $sheet, Purchase $purchaseOrder, int $lastRow): int
    {
        $blocks = $this->buildBlocks($purchaseOrder);
        $firstRow = $lastRow + 1;

        if ($blocks->isEmpty()) {
            $this->writeRow($sheet, $firstRow, array_merge(
                $this->purchaseOrderColumns($purchaseOrder),
                ['-', '-', '-', '-', '-', 0, '-', 0, 0, 0, 0],
                [0]
            ));

            return $firstRow;
        }

        $currentRow = $lastRow;
        $isFirstRowOfOrder = true;
        $orderTotal = $blocks->sum(fn (array $block) => $block['total'] ?? 0);

        foreach ($blocks as $block) {
            $blockFirstRow = $currentRow + 1;

            foreach ($block['rows'] as $index => $itemColumns) {
                $currentRow++;

                $values = $isFirstRowOfOrder
                    ? $this->purchaseOrderColumns($purchaseOrder)
                    : [null, null, null, null];

                if ($index === 0) {
                    array_push($values, $block['number'], $block['date'], $block['payment']);
                } else {
                    array_push($values, null, null, null);
                }

                $values = array_merge($values, $itemColumns);

                // Kolom O dan P hanya diisi di baris pertama tiap blok / PO.
                $values[] = $index === 0 ? $block['total'] : null;
                $values[] = $isFirstRowOfOrder ? $orderTotal : null;

                $this->writeRow($sheet, $currentRow, $values);
                $isFirstRowOfOrder = false;
            }

            if ($currentRow > $blockFirstRow) {
                foreach (self::PL_COLUMNS as $column) {
                    $sheet->mergeCells($column.$blockFirstRow.':'.$column.$currentRow);
                }
            }
        }

        if ($currentRow > $firstRow) {
            foreach (self::PO_COLUMNS as $column) {
                $sheet->mergeCells($column.$firstRow.':'.$column.$currentRow);
            }
        }

        return $currentRow;
    }

    /**
     * Susun blok baris: satu blok per purchase list. PO tidak punya item
     * sendiri (cuma wadah per supplier), jadi tidak ada blok sisa qty PO.
     */
    private function buildBlocks(Purchase $purchaseOrder): Collection
    {
        return $purchaseOrder->purchaseLists->map(function (Purchase $purchaseList) {
            $rows = $purchaseList->purchaseItems
                ->map(fn (PurchaseItem $item) => $this->purchaseListItemColumns($item))
                ->values()
                ->all();

            if ($rows === []) {
                return null;
            }

            return [
                'number' => $purchaseList->purchase_number,
                'date' => Carbon::parse($purchaseList->purchase_date)->format('d/m/Y H:i'),
                'payment' => $purchaseList->payment_status ?: '-',
                'total' => (float) $purchaseList->purchaseItems->sum('subtotal'),
                'rows' => $rows,
            ];
        })->filter()->values();
    }

    /**
     * Kolom H sampai N untuk satu item purchase list.
     */
    private function purchaseListItemColumns(PurchaseItem $item): array
    {
        $quantity = (float) $item->quantity;
        $unitPrice = (float) $item->final_price;
        $totalAmount = (float) $item->subtotal;

        // Fallback bila kolom hasil kalkulasi belum terisi (data lama).
        if ($unitPrice <= 0) {
            $unitPrice = (float) $item->price_after_tax + (float) $item->freight;
        }

        if ($totalAmount <= 0) {
            $totalAmount = $unitPrice * $quantity;
        }

        $stockInBase = (float) $item->inventoryItems->sum('stock_in');
        $stockIn = $stockInBase / max(1, (float) ($item->unit_conversion_value ?? 1));

        return [
            $item->purchaseProduct->name ?? '-',
            $item->purchaseProduct->sku ?? '-',
            $quantity,
            $item->unit_name ?? '-',
            $unitPrice,
            $totalAmount,
            $stockIn,
        ];
    }

    /**
     * Kolom A sampai D.
     */
    private function purchaseOrderColumns(Purchase $purchaseOrder): array
    {
        // Purchase List beserta stock in-nya sudah di-eager load, jadi label
        // status dihitung dari situ — bukan lewat query ulang per baris.
        $lists = $purchaseOrder->purchaseLists;
        $listItems = $lists->flatMap(fn ($list) => $list->purchaseItems);

        $stockInCompleted = $listItems->isNotEmpty()
            && $listItems->every(fn ($item) => $item->isFullyStockedIn());

        $fullyPaid = $lists->isNotEmpty() && $lists->every(
            fn ($list) => in_array($list->payment_status, ['Paid', 'Overpaid'], true)
                || (float) $list->total_amount <= 0
        );

        return [
            $purchaseOrder->purchase_number,
            Carbon::parse($purchaseOrder->purchase_date)->format('d/m/Y H:i'),
            $purchaseOrder->supplier->name ?? '-',
            $purchaseOrder->approvalStatusLabel($stockInCompleted, $fullyPaid),
        ];
    }

    /**
     * Warnai grup kolom Purchase List dan Produk agar hierarkinya terbaca.
     */
    private function styleColumnGroups(Worksheet $sheet): void
    {
        $sheet->getStyle('E1:G1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2E5C8A');

        $sheet->getStyle('H1:N1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF3D7AB8');
    }

    /**
     * @return int baris terakhir tabel setelah baris TOTAL ditulis
     */
    private function writeTotalRow(Worksheet $sheet, int $lastDataRow): int
    {
        if ($lastDataRow < 2) {
            return $lastDataRow;
        }

        $totalRow = $lastDataRow + 1;
        $sheet->setCellValue('L'.$totalRow, 'TOTAL');

        foreach (['M', 'O', 'P'] as $column) {
            $sheet->setCellValue(
                $column.$totalRow,
                '=SUM('.$column.'2:'.$column.$lastDataRow.')'
            );
        }

        $sheet->getStyle('A'.$totalRow.':P'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('M'.$totalRow.':P'.$totalRow)
            ->getNumberFormat()
            ->setFormatCode(self::CURRENCY_FORMAT);

        return $totalRow;
    }
}
