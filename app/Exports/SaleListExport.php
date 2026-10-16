<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
        'Harga Modal',
        'Margin',
        'Ket. Modal',
        'Grand Total',
    ];

    /** Kolom yang di-merge per order karena nilainya milik order, bukan per produk. */
    private const ORDER_COLUMNS = ['A', 'B', 'C', 'N'];

    /** Kolom angka rupiah (1 = A). */
    private const CURRENCY_COLUMNS = [10, 11, 12, 14];

    /**
     * Isi kolom Ket. Modal untuk baris yang sebagian modalnya tidak tertutup
     * batch mana pun. Barang yang terjual melebihi stok yang sudah di-stock in
     * pada tanggal penjualan itu dinilai dengan harga batch terakhir, jadi
     * angkanya taksiran dan bisa berubah setelah stock in-nya dibereskan.
     */
    private const LABEL_TAKSIRAN = 'Taksiran';

    /** Warna baris bermodal taksiran: kuning lembut, menang atas warna belang. */
    private const TAKSIRAN_COLOR = 'FFFFF2CC';

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
                // Harga modal FIFO sudah dihitung di muka oleh cost:rebuild-fifo,
                // jadi export cukup membacanya, tidak menghitung ulang di sini.
                'orderItems.cost',
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
        $this->highlightEstimatedRows($sheet, $lastRow);

        if ($row >= 2) {
            $sheet->getStyle('G2:G'.$row)->getNumberFormat()->setFormatCode('#,##0');

            // Harga Modal dan Margin hasil rata-rata tertimbang FIFO jarang bulat:
            // 2.500@200 + 500@220 memberi modal 203,33 dan margin 76,67. Format
            // rupiah tanpa desimal menampilkannya sebagai 203 dan 77.
            $sheet->getStyle('K2:L'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Matikan auto-size untuk seluruh kolom. Writer akan memindai setiap sel
        // untuk kolom yang masih auto-size; pada export ribuan item biaya ini
        // sangat besar dan tidak memberi manfaat dibanding lebar yang stabil.
        foreach ([
            'A' => 22, 'B' => 17, 'C' => 28, 'D' => 42, 'E' => 18,
            'F' => 12, 'G' => 12, 'H' => 14, 'I' => 14, 'J' => 16,
            'K' => 16, 'L' => 16, 'M' => 13, 'N' => 18,
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
                0, 0, null,
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

        // Samakan dengan kolom Price di halaman Sale List.
        $unitPrice = (float) ($item->discount_price ?? $item->price ?? 0);

        return [
            $name,
            $sku,
            $type,
            (float) $item->quantity,
            $item->unit_name ?? '-',
            $item->mode ? ucfirst(strtolower($item->mode)) : '-',
            $unitPrice,
            ...$this->costColumns($item, $unitPrice),
        ];
    }

    /**
     * Kolom Harga Modal dan Margin untuk satu item.
     *
     * Nilainya dari order_item_costs, hasil alokasi FIFO command
     * cost:rebuild-fifo. Baris yang belum pernah dihitung ditulis 0 supaya
     * kolomnya tetap bisa dijumlahkan, bukan bercampur teks.
     *
     * Harga Modal adalah rata-rata tertimbang batch yang termakan baris ini:
     * kalau satu penjualan menghabiskan sisa batch lama lalu lanjut ke batch
     * berikutnya, angkanya jatuh di antara kedua harga batch itu.
     *
     * Keduanya PER SATUAN JUAL, bukan dikali qty: jual 600 dengan modal 500
     * memberi margin 100, berapa pun jumlah yang terjual. Satuannya sama dengan
     * Unit Price di sebelahnya, jadi pada baris yang dijual per Dus ketiganya
     * sama-sama harga per Dus.
     */
    private function costColumns(OrderItem $item, float $unitPrice): array
    {
        $cost = $item->cost;

        if ($cost === null) {
            return [0, 0, null];
        }

        $quantity = (float) $item->quantity;
        $unitCost = $quantity > 0 ? $cost->total_cost / $quantity : $cost->unit_cost;

        return [
            $unitCost,
            $unitPrice - $unitCost,
            $cost->is_estimated ? self::LABEL_TAKSIRAN : null,
        ];
    }

    /**
     * Warnai seluruh baris yang modalnya taksiran.
     *
     * Lewat conditional formatting, bukan style per sel, dengan alasan yang
     * sama seperti warna belang di finalizeSheet(): satu aturan untuk seluruh
     * tabel. Aturannya ditaruh di depan warna belang supaya menang di baris
     * genap; kalau dipasang terpisah, belangnya yang menimpa kuningnya.
     */
    private function highlightEstimatedRows(Worksheet $sheet, int $lastRow): void
    {
        if ($lastRow < 2) {
            return;
        }

        $range = 'A2:'.Coordinate::stringFromColumnIndex(count(self::HEADERS)).$lastRow;

        $estimated = new Conditional;
        $estimated->setConditionType(Conditional::CONDITION_EXPRESSION)
            ->addCondition('$M2="'.self::LABEL_TAKSIRAN.'"')
            ->setStopIfTrue(true);
        $estimated->getStyle()->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::TAKSIRAN_COLOR);

        $sheet->getStyle($range)->setConditionalStyles([
            $estimated,
            ...$sheet->getStyle($range)->getConditionalStyles(),
        ]);
    }

    /**
     * Beri warna berbeda pada grup kolom produk agar mudah dibaca.
     */
    private function styleProductHeaderGroup(Worksheet $sheet): void
    {
        $sheet->getStyle('D1:M1')->getFill()
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
        $sheet->setCellValue('M'.$totalRow, 'TOTAL');

        // Harga Modal dan Margin sekarang harga per satuan, jadi tidak ikut
        // dijumlahkan: menotal harga per satuan antar produk tidak berarti
        // apa-apa. Yang ditotal hanya Grand Total.
        $sheet->setCellValue(
            'N'.$totalRow,
            '=SUM(N2:N'.$lastDataRow.')'
        );

        $sheet->getStyle('A'.$totalRow.':N'.$totalRow)->getFont()->setBold(true);
        $sheet->getStyle('N'.$totalRow)
            ->getNumberFormat()
            ->setFormatCode(self::CURRENCY_FORMAT);

        return $totalRow;
    }
}
