<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SaleListExport extends BaseExcelExport
{
    private const CHUNK_SIZE = 300;

    private const HEADERS = [
        'Invoice Number',
        'Order Date',
        'Customer',
        'Nama Product',
        'SKU',
        'Type',
        'Qty',
        'Unit',
        'Mode',
        'Unit Price',
        'Grand Total',
    ];

    /** Kolom yang di-merge per order karena nilainya milik order, bukan per produk. */
    private const ORDER_COLUMNS = ['A', 'B', 'C', 'K'];

    /** Kolom angka rupiah (1 = A). */
    private const CURRENCY_COLUMNS = [10, 11];

    /** Kolom teks panjang yang rata kiri: Customer, Nama Product, dan SKU. */
    private const TEXT_COLUMNS = [3, 4, 5];

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
                'orderItems.product:id,name,sku',
                'orderItems.productBundle:id,sku',
                'orderItems.productBundle.items.product:id,name',
            ])
            ->chunk(self::CHUNK_SIZE, function ($orders) use ($sheet, &$row) {
                foreach ($orders as $order) {
                    $row = $this->writeOrder($sheet, $order, $row);
                }
            });

        $lastRow = $this->writeTotalRow($sheet, $row);
        $this->finalizeSheet($sheet, count(self::HEADERS), $lastRow, self::CURRENCY_COLUMNS, self::TEXT_COLUMNS);

        if ($row >= 2) {
            $sheet->getStyle('G2:G'.$row)->getNumberFormat()->setFormatCode('#,##0');
        }

        // Matikan auto-size untuk seluruh kolom. Writer akan memindai setiap sel
        // untuk kolom yang masih auto-size; pada export ribuan item biaya ini
        // sangat besar dan tidak memberi manfaat dibanding lebar yang stabil.
        foreach ([
            'A' => 22, 'B' => 17, 'C' => 28, 'D' => 42, 'E' => 18,
            'F' => 12, 'G' => 12, 'H' => 14, 'I' => 14, 'J' => 16,
            'K' => 16,
        ] as $column => $width) {
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
                '-', '-', '-', 0, '-', '-', 0,
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
        }

        return $currentRow;
    }

    /**
     * Kolom D sampai J untuk satu order item.
     */
    private function itemColumns(OrderItem $item): array
    {
        if ($item->productBundle) {
            $name = $item->productBundle->items
                ->map(fn ($bundleItem) => $bundleItem->product->name ?? '-')
                ->implode(' + ');
            $sku = $item->productBundle->sku ?: '-';
            $type = 'Bundle';
        } else {
            $name = $item->product->name ?? ($item->product_name ?: '-');
            $sku = $item->product->sku ?? '-';
            $type = 'Satuan';
        }

        return [
            $name,
            $sku,
            $type,
            (float) $item->quantity,
            $item->unit_name ?? '-',
            $item->mode ? ucfirst(strtolower($item->mode)) : '-',
            // Samakan dengan kolom Price di halaman Sale List.
            (float) ($item->discount_price ?? $item->price ?? 0),
        ];
    }

    /**
     * Beri warna berbeda pada grup kolom produk agar mudah dibaca.
     */
    private function styleProductHeaderGroup(Worksheet $sheet): void
    {
        $sheet->getStyle('D1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2E5C8A');
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
        $sheet->setCellValue('J'.$totalRow, 'TOTAL');

        foreach (['K'] as $column) {
            $sheet->setCellValue(
                $column.$totalRow,
                '=SUM('.$column.'2:'.$column.$lastDataRow.')'
            );
        }

        $sheet->getStyle('A'.$totalRow.':K'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('K'.$totalRow.':K'.$totalRow)
            ->getNumberFormat()
            ->setFormatCode(self::CURRENCY_FORMAT);

        return $totalRow;
    }
}
