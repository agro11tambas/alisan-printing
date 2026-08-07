<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SaleListExport extends BaseExcelExport
{
    private const CHUNK_SIZE = 300;

    private const HEADERS = [
        'Invoice Number',
        'Order Date',
        'Customer',
        'Nama Product',
        'Type',
        'Qty',
        'Unit',
        'Mode',
        'Unit Price',
        'Total Amount',
        'Diskon',
        'Total',
        'Diskon',
        'Grand Total',
    ];

    /** Kolom yang di-merge per order karena nilainya milik order, bukan per produk. */
    private const ORDER_COLUMNS = ['A', 'B', 'C', 'M', 'N'];

    /** Kolom angka rupiah (1 = A). */
    private const CURRENCY_COLUMNS = [9, 10, 11, 12, 13, 14];

    public function __construct(private Builder $query)
    {
        parent::__construct();
    }

    protected function build(): void
    {
        $sheet = $this->makeSheet('Sale List', true);

        $this->writeHeader($sheet, self::HEADERS, false);
        $this->styleProductHeaderGroup($sheet);

        $row = 1;

        $this->query
            ->with([
                'customer:id,name',
                'orderItems.product:id,name',
                'orderItems.productBundle:id',
                'orderItems.productBundle.items.product:id,name',
            ])
            ->chunk(self::CHUNK_SIZE, function ($orders) use ($sheet, &$row) {
                foreach ($orders as $order) {
                    $row = $this->writeOrder($sheet, $order, $row);
                }
            });

        $this->writeTotalRow($sheet, $row);
        $this->finalizeSheet($sheet, count(self::HEADERS), $row, self::CURRENCY_COLUMNS);

        if ($row >= 2) {
            $sheet->getStyle('F2:F'.$row)->getNumberFormat()->setFormatCode('#,##0');
        }

        // Auto size mengabaikan isi merge cell, jadi kolom order diberi lebar tetap.
        foreach (['A' => 22, 'B' => 17, 'C' => 28, 'M' => 14, 'N' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setAutoSize(false);
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    /**
     * Tulis satu order: satu baris per produk, kolom order di-merge.
     *
     * @return int nomor baris terakhir yang terpakai
     */
    private function writeOrder(Worksheet $sheet, Order $order, int $lastRow): int
    {
        $items = $order->orderItems;
        $firstRow = $lastRow + 1;

        $invoice = $order->order_number;
        $orderDate = Carbon::parse($order->order_date)->format('d/m/Y H:i');
        $customer = $order->customer->name ?? '-';

        if ($items->isEmpty()) {
            $this->writeRow($sheet, $firstRow, [
                $invoice,
                $orderDate,
                $customer,
                '-', '-', 0, '-', '-', 0, 0, 0, 0,
                (float) $order->discount,
                (float) $order->grand_total,
            ]);

            return $firstRow;
        }

        $currentRow = $lastRow;

        foreach ($items as $index => $item) {
            $currentRow++;
            $values = $this->itemColumns($item);

            if ($index === 0) {
                array_unshift($values, $invoice, $orderDate, $customer);
                $values[] = (float) $order->discount;
                $values[] = (float) $order->grand_total;
            } else {
                array_unshift($values, null, null, null);
            }

            $this->writeRow($sheet, $currentRow, $values);
        }

        if ($currentRow > $firstRow) {
            foreach (self::ORDER_COLUMNS as $column) {
                $sheet->mergeCells($column.$firstRow.':'.$column.$currentRow);
            }

            $sheet->getStyle('A'.$firstRow.':N'.$currentRow)
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP);
        }

        return $currentRow;
    }

    /**
     * Kolom D sampai L untuk satu order item.
     */
    private function itemColumns(OrderItem $item): array
    {
        if ($item->productBundle) {
            $name = $item->productBundle->items
                ->map(fn ($bundleItem) => $bundleItem->product->name ?? '-')
                ->implode(' + ');
            $type = 'Bundle';
        } else {
            $name = $item->product->name ?? ($item->product_name ?: '-');
            $type = 'Satuan';
        }

        $quantity = (float) $item->quantity;
        $unitPrice = (float) $item->price;
        $totalAmount = (float) $item->subtotal;
        $total = (float) $item->total_after_discount;

        // Fallback bila kolom hasil kalkulasi belum terisi (data lama).
        if ($totalAmount <= 0) {
            $totalAmount = $unitPrice * $quantity;
        }

        if ($total <= 0) {
            $total = (float) ($item->discount_price ?: $item->price) * $quantity;
        }

        return [
            $name,
            $type,
            $quantity,
            $item->unit_name ?? '-',
            $item->mode ? ucfirst(strtolower($item->mode)) : '-',
            $unitPrice,
            $totalAmount,
            $totalAmount - $total,
            $total,
        ];
    }

    /**
     * Beri warna berbeda pada grup kolom produk agar mudah dibaca.
     */
    private function styleProductHeaderGroup(Worksheet $sheet): void
    {
        $sheet->getStyle('D1:L1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2E5C8A');
    }

    private function writeTotalRow(Worksheet $sheet, int $lastDataRow): void
    {
        if ($lastDataRow < 2) {
            return;
        }

        $totalRow = $lastDataRow + 1;
        $sheet->setCellValue('I'.$totalRow, 'TOTAL');

        foreach (['J', 'K', 'L', 'M', 'N'] as $column) {
            $sheet->setCellValue(
                $column.$totalRow,
                '=SUM('.$column.'2:'.$column.$lastDataRow.')'
            );
        }

        $sheet->getStyle('A'.$totalRow.':N'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('J'.$totalRow.':N'.$totalRow)
            ->getNumberFormat()
            ->setFormatCode(self::CURRENCY_FORMAT);
    }
}
