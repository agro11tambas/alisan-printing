<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class BaseExcelExport
{
    protected const CURRENCY_FORMAT = '#,##0';

    protected const HEADER_COLOR = 'FF1F3864';

    protected const BORDER_COLOR = 'FFB7C3D6';

    protected Spreadsheet $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet;
    }

    /**
     * Bangun seluruh sheet lalu kirim sebagai unduhan .xlsx.
     */
    public function download(string $filename): StreamedResponse
    {
        $this->build();

        $spreadsheet = $this->spreadsheet;
        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    abstract protected function build(): void;

    /**
     * Tulis baris judul kolom pada baris pertama sheet.
     */
    protected function writeHeader(Worksheet $sheet, array $headers, bool $autoFilter = true): void
    {
        $sheet->fromArray($headers, null, 'A1', true);

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = 'A1:'.$lastColumn.'1';

        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::HEADER_COLOR);
        $sheet->getStyle($headerRange)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setWrapText(true);

        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->freezePane('A2');

        // Autofilter tidak dipakai pada sheet yang memakai merge cell.
        if ($autoFilter) {
            $sheet->setAutoFilter($headerRange);
        }
    }

    /**
     * Tulis satu baris data mulai kolom A.
     */
    protected function writeRow(Worksheet $sheet, int $rowNumber, array $values): void
    {
        $sheet->fromArray($values, null, 'A'.$rowNumber, true);
    }

    /**
     * Rapikan lebar kolom, perataan, garis, dan format angka setelah semua
     * baris ditulis. $lastRow adalah baris terakhir tabel, termasuk baris TOTAL.
     *
     * @param  array<int, int>  $currencyColumns  Nomor kolom (1 = A) yang diformat ribuan.
     */
    protected function finalizeSheet(Worksheet $sheet, int $columnCount, int $lastRow, array $currencyColumns = []): void
    {
        for ($column = 1; $column <= $columnCount; $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        if ($lastRow < 2) {
            return;
        }

        $lastColumn = Coordinate::stringFromColumnIndex($columnCount);

        // Isi sel ditengahkan supaya sejajar dengan judul kolomnya. Rata tengah
        // vertikal juga membuat sel yang di-merge duduk di tengah bloknya.
        $sheet->getStyle('A2:'.$lastColumn.$lastRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        foreach ($currencyColumns as $column) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $range = $letter.'2:'.$letter.$lastRow;

            $sheet->getStyle($range)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);

            // Rupiah tetap rata kanan supaya satuan, ribuan, dan jutaan sejajar.
            $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Garis tipis: batas antar baris dan antar blok merge jadi kelihatan.
        $sheet->getStyle('A1:'.$lastColumn.$lastRow)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new Color(self::BORDER_COLOR));
    }

    /**
     * Buat sheet baru dengan nama yang aman untuk Excel (maks 31 karakter).
     */
    protected function makeSheet(string $title, bool $first = false): Worksheet
    {
        $title = mb_substr(str_replace(['\\', '/', '*', '?', ':', '[', ']'], ' ', $title), 0, 31);

        if ($first) {
            $sheet = $this->spreadsheet->getActiveSheet();
            $sheet->setTitle($title);

            return $sheet;
        }

        return $this->spreadsheet->createSheet()->setTitle($title);
    }
}
