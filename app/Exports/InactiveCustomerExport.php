<?php

namespace App\Exports;

use App\Models\CustomerAddresses;
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
        'Email Akun',
        'Nama Usaha',
        'Alamat',
        'Sales',
        'Deposit',
    ];

    /** Kolom nomor telepon, ditulis sebagai teks supaya angka 0 / 62 di depan tidak berubah. */
    private const PHONE_COLUMNS = ['E', 'F'];

    /** Kolom angka rupiah (1 = A). */
    private const CURRENCY_COLUMNS = [11];

    /** Kolom teks panjang yang rata kiri. */
    private const TEXT_COLUMNS = [2, 6, 7, 8, 9, 10];

    public function __construct(private Builder $query, private Carbon $inactiveCutoff, private int $inactiveMonths)
    {
        parent::__construct();
    }

    protected function build(): void
    {
        $sheet = $this->makeSheet('Customer Tidak Order', true);

        $this->writeHeader($sheet, self::HEADERS);

        $row = 1;

        $this->query
            ->with([
                'user:id,name',
                'accounts:customer_accounts.id,whatsapp_number,email',
                'addresses:id,customer_id,business_name,address,is_default',
            ])
            ->chunk(self::CHUNK_SIZE, function ($customers) use ($sheet, &$row) {
                foreach ($customers as $customer) {
                    $row++;
                    $this->writeCustomer($sheet, $customer, $row);
                }
            });

        $this->finalizeSheet($sheet, count(self::HEADERS), $row, self::CURRENCY_COLUMNS, self::TEXT_COLUMNS);
    }

    private function writeCustomer(Worksheet $sheet, Customers $customer, int $row): void
    {
        $lastOrderDate = $customer->orders_max_order_date;
        $address = $this->defaultAddress($customer);

        $this->writeRow($sheet, $row, [
            $row - 1,
            $customer->name,
            $lastOrderDate ? 'Tidak order '.$this->inactiveMonths.' bulan' : 'Belum pernah order',
            $lastOrderDate ? Carbon::parse($lastOrderDate)->format('d/m/Y') : '-',
            null,
            null,
            $this->joinUnique($customer->accounts->pluck('email')),
            $address?->business_name ?: '-',
            $address?->address ?: '-',
            $customer->user?->name ?? '-',
            (float) $customer->customer_deposit,
        ]);

        $phones = [
            $customer->phone ?: '-',
            $this->joinUnique($customer->accounts->pluck('whatsapp_number')),
        ];

        foreach (self::PHONE_COLUMNS as $index => $column) {
            $sheet->setCellValueExplicit($column.$row, $phones[$index], DataType::TYPE_STRING);
        }
    }

    /**
     * Alamat default customer, atau alamat pertama bila tidak ada yang ditandai default.
     */
    private function defaultAddress(Customers $customer): ?CustomerAddresses
    {
        return $customer->addresses->firstWhere('is_default', true)
            ?? $customer->addresses->first();
    }

    /**
     * Gabung beberapa nilai akun (satu customer bisa punya lebih dari satu akun).
     */
    private function joinUnique($values): string
    {
        $joined = $values->filter()->unique()->implode(', ');

        return $joined !== '' ? $joined : '-';
    }
}
