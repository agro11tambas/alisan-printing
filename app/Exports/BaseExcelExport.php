<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class BaseExcelExport
{
    protected const CURRENCY_FORMAT = '#,##0';

    protected const HEADER_COLOR = 'FF1F3864';

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
     * Rapikan lebar kolom dan format angka setelah semua baris ditulis.
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

        foreach ($currencyColumns as $column) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $sheet->getStyle($letter.'2:'.$letter.$lastRow)
                ->getNumberFormat()
                ->setFormatCode(self::CURRENCY_FORMAT);
        }
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
