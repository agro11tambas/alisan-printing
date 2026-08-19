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
        'PO Qty',
        'Qty',
        'Unit',
        'Unit Price',
        'Total Amount',
        'Stock In',
        'Total Purchase List',
        'Total Purchase Order',
    ];

    /** Kolom milik header PO, di-merge sepanjang seluruh blok PO. */
    private const PO_COLUMNS = ['A', 'B', 'C', 'D', 'Q'];

    /** Kolom milik header Purchase List, di-merge sepanjang blok PL. */
    private const PL_COLUMNS = ['E', 'F', 'G', 'P'];

    /** Kolom angka rupiah (1 = A). */
    private const CURRENCY_COLUMNS = [13, 14, 16, 17];

    /** Kolom teks panjang yang rata kiri: Supplier dan Nama Product. */
    private const TEXT_COLUMNS = [3, 8];

    /** Kolom kuantitas. */
    private const QTY_COLUMNS = ['J', 'K', 'O'];

    private const NOT_VERIFIED_LABEL = 'Belum diverifikasi';

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
                'purchaseItems.purchaseProduct:id,name,sku',
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
        $fixedWidths = ['A' => 20, 'B' => 17, 'C' => 22, 'D' => 12, 'E' => 20, 'F' => 17, 'G' => 13, 'P' => 19, 'Q' => 20];

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
                ['-', '-', '-', '-', '-', 0, 0, '-', 0, 0, 0],
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

                // Kolom P dan Q hanya diisi di baris pertama tiap blok / PO.
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
     * Susun blok baris: satu blok per purchase list, lalu satu blok berisi
     * sisa qty PO yang belum diverifikasi jadi purchase list.
     */
    private function buildBlocks(Purchase $purchaseOrder): Collection
    {
        $orderItems = $purchaseOrder->purchaseItems->keyBy('id');

        $blocks = $purchaseOrder->purchaseLists->map(function (Purchase $purchaseList) use ($orderItems) {
            $rows = $purchaseList->purchaseItems
                ->map(fn (PurchaseItem $item) => $this->purchaseListItemColumns($item, $orderItems))
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

        $remainingRows = $this->remainingRows($purchaseOrder, $orderItems);

        if ($remainingRows !== []) {
            $blocks->push([
                'number' => self::NOT_VERIFIED_LABEL,
                'date' => '-',
                'payment' => '-',
                'total' => null,
                'rows' => $remainingRows,
            ]);
        }

        return $blocks;
    }

    /**
     * Kolom H sampai O untuk satu item purchase list.
     */
    private function purchaseListItemColumns(PurchaseItem $item, Collection $orderItems): array
    {
        $sourceItem = $orderItems->get($item->source_purchase_item_id);

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
            $sourceItem ? (float) $sourceItem->quantity : 0,
            $quantity,
            $item->unit_name ?? '-',
            $unitPrice,
            $totalAmount,
            $stockIn,
        ];
    }

    /**
     * Baris untuk qty PO yang belum terserap ke purchase list mana pun.
     */
    private function remainingRows(Purchase $purchaseOrder, Collection $orderItems): array
    {
        $verifiedBySource = $purchaseOrder->purchaseLists
            ->flatMap(fn (Purchase $purchaseList) => $purchaseList->purchaseItems)
            ->groupBy('source_purchase_item_id')
            ->map(fn (Collection $items) => (float) $items->sum('quantity'));

        return $orderItems
            ->map(function (PurchaseItem $item) use ($verifiedBySource) {
                $orderedQty = (float) $item->quantity;
                $remaining = $orderedQty - $verifiedBySource->get($item->id, 0);

                if ($remaining <= 0) {
                    return null;
                }

                return [
                    $item->purchaseProduct->name ?? '-',
                    $item->purchaseProduct->sku ?? '-',
                    $orderedQty,
                    $remaining,
                    $item->unit_name ?? '-',
                    0,
                    0,
                    0,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Kolom A sampai D.
     */
    private function purchaseOrderColumns(Purchase $purchaseOrder): array
    {
        return [
            $purchaseOrder->purchase_number,
            Carbon::parse($purchaseOrder->purchase_date)->format('d/m/Y H:i'),
            $purchaseOrder->supplier->name ?? '-',
            $purchaseOrder->approval_status_label,
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

        $sheet->getStyle('H1:O1')->getFill()
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
        $sheet->setCellValue('M'.$totalRow, 'TOTAL');

        foreach (['N', 'P', 'Q'] as $column) {
            $sheet->setCellValue(
                $column.$totalRow,
                '=SUM('.$column.'2:'.$column.$lastDataRow.')'
            );
        }

        $sheet->getStyle('A'.$totalRow.':Q'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('N'.$totalRow.':Q'.$totalRow)
            ->getNumberFormat()
            ->setFormatCode(self::CURRENCY_FORMAT);

        return $totalRow;
    }
}
