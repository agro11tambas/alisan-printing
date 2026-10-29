<?php

namespace App\Exports;

use App\Models\Customers;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InactiveCustomerExport extends BaseExcelExport
{
    private const CHUNK_SIZE = 200;

    private const HEADERS = [
        'No',
        'Nama Customer',
        'Status',
        'Order Terakhir',
        'No. HP Customer',
        'No. WhatsApp Akun',
        'Nama Usaha',
        'Alamat',
    ];

    /** Kolom milik customer, di-merge sepanjang seluruh blok customer. */
    private const CUSTOMER_COLUMNS = ['A', 'B', 'C', 'D', 'E'];

    /** Kolom nomor telepon, ditulis sebagai teks supaya angka 0 / 62 di depan tidak berubah. */
    private const PHONE_COLUMNS = ['E', 'F'];

    /** Kolom teks panjang yang rata kiri. */
    private const TEXT_COLUMNS = [2, 7, 8];

    public function __construct(private Builder $query, private Carbon $inactiveCutoff, private int $inactiveMonths)
    {
        parent::__construct();
    }

    protected function build(): void
    {
        $sheet = $this->makeSheet('Customer Tidak Order', true);

        $this->writeHeader($sheet, self::HEADERS, false);

        $row = 1;
        $number = 0;

        $this->query
            ->with([
                'accounts:customer_accounts.id,whatsapp_number',
                'addresses' => fn ($query) => $query
                    ->select('id', 'customer_id', 'business_name', 'address', 'is_default')
                    ->orderByDesc('is_default')
                    ->orderBy('id'),
            ])
            ->chunk(self::CHUNK_SIZE, function ($customers) use ($sheet, &$row, &$number) {
                foreach ($customers as $customer) {
                    $row = $this->writeCustomer($sheet, $customer, ++$number, $row);
                }
            });

        $this->finalizeSheet($sheet, count(self::HEADERS), $row, [], self::TEXT_COLUMNS);
    }

    /**
     * Tulis satu customer. Tiap nomor WhatsApp dan tiap alamat mendapat
     * barisnya sendiri; kolom customer di-merge sepanjang bloknya.
     *
     * @return int nomor baris terakhir yang terpakai
     */
    private function writeCustomer(Worksheet $sheet, Customers $customer, int $number, int $lastRow): int
    {
        $lastOrderDate = $customer->orders_max_order_date;

        $whatsappNumbers = $customer->accounts->pluck('whatsapp_number')->filter()->unique()->values();
        $addresses = $customer->addresses->values();
        $rowCount = max(1, $whatsappNumbers->count(), $addresses->count());

        $firstRow = $lastRow + 1;

        for ($index = 0; $index < $rowCount; $index++) {
            $row = $firstRow + $index;
            $address = $addresses->get($index);

            $this->writeRow($sheet, $row, $index === 0
                ? [
                    $number,
                    $customer->name,
                    $lastOrderDate ? 'Tidak order '.$this->inactiveMonths.' bulan' : 'Belum pernah order',
                    $lastOrderDate ? Carbon::parse($lastOrderDate)->format('d/m/Y') : '-',
                    null,
                    null,
                    $address?->business_name ?: '-',
                    $address?->address ?: '-',
                ]
                : [null, null, null, null, null, null, $address?->business_name, $address?->address]);

            $phones = [
                $index === 0 ? ($customer->phone ?: '-') : null,
                $whatsappNumbers->get($index) ?? ($index === 0 ? '-' : null),
            ];

            foreach (self::PHONE_COLUMNS as $columnIndex => $column) {
                if ($phones[$columnIndex] !== null) {
                    $sheet->setCellValueExplicit($column.$row, $phones[$columnIndex], DataType::TYPE_STRING);
                }
            }
        }

        $lastRow = $firstRow + $rowCount - 1;

        if ($lastRow > $firstRow) {
            foreach (self::CUSTOMER_COLUMNS as $column) {
                $sheet->mergeCells($column.$firstRow.':'.$column.$lastRow);
            }
        }

        return $lastRow;
    }
}
