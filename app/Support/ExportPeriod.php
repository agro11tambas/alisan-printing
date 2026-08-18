<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Periode yang dipilih pengguna di modal export Excel: per bulan, per tahun,
 * atau rentang tanggal bebas.
 *
 * Periode ini MENGGANTIKAN filter tanggal halaman. Filter lain (pencarian,
 * status pembayaran, tab Edited) tetap ikut apa adanya.
 */
final class ExportPeriod
{
    private function __construct(
        private readonly Carbon $start,
        private readonly Carbon $end,
        private readonly string $filenameSuffix,
    ) {}

    /**
     * Pengguna memilih periode sendiri, bukan "Ikuti Filter Halaman".
     */
    public static function isRequested(Request $request): bool
    {
        return in_array($request->input('export_period'), ['month', 'year', 'range'], true);
    }

    /**
     * null kalau tidak ada periode yang diminta ATAU isiannya tidak valid.
     * Pemanggil membedakan keduanya lewat isRequested().
     */
    public static function fromRequest(Request $request): ?self
    {
        return match ($request->input('export_period')) {
            'month' => self::month($request->input('export_month'), $request->input('export_year')),
            'year' => self::year($request->input('export_year')),
            'range' => self::range($request->input('export_start_date'), $request->input('export_end_date')),
            default => null,
        };
    }

    /**
     * Daftar tahun untuk dropdown, dari tahun sekarang mundur sampai tahun
     * data paling lama. $earliestDate diambil dari min() kolom tanggal.
     *
     * @return array<int, int>
     */
    public static function yearOptions(?string $earliestDate): array
    {
        $currentYear = (int) Carbon::now()->year;

        $earliestYear = $earliestDate
            ? (int) Carbon::parse($earliestDate)->year
            : $currentYear;

        return range($currentYear, min($earliestYear, $currentYear));
    }

    /**
     * Batasi query ke rentang periode ini.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     */
    public function applyTo($query, string $dateColumn): void
    {
        $query->whereBetween($dateColumn, [$this->start, $this->end]);
    }

    /**
     * Potongan nama file yang menjelaskan periodenya, misal "2026-08".
     */
    public function filenameSuffix(): string
    {
        return $this->filenameSuffix;
    }

    private static function month(mixed $month, mixed $year): ?self
    {
        $month = filter_var($month, FILTER_VALIDATE_INT);
        $year = self::validYear($year);

        if ($year === null || $month === false || $month < 1 || $month > 12) {
            return null;
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();

        return new self($start, $start->copy()->endOfMonth(), $start->format('Y-m'));
    }

    private static function year(mixed $year): ?self
    {
        $year = self::validYear($year);

        if ($year === null) {
            return null;
        }

        $start = Carbon::create($year, 1, 1)->startOfYear();

        return new self($start, $start->copy()->endOfYear(), (string) $year);
    }

    private static function range(mixed $start, mixed $end): ?self
    {
        if (! is_string($start) || ! is_string($end) || $start === '' || $end === '') {
            return null;
        }

        try {
            $from = Carbon::parse($start)->startOfDay();
            $to = Carbon::parse($end)->endOfDay();
        } catch (\Throwable) {
            return null;
        }

        // Tanggal kebalik dianggap salah ketik, bukan error.
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return new self($from, $to, $from->format('Ymd').'-'.$to->format('Ymd'));
    }

    private static function validYear(mixed $year): ?int
    {
        $year = filter_var($year, FILTER_VALIDATE_INT);

        if ($year === false || $year < 2000 || $year > 2100) {
            return null;
        }

        return $year;
    }
}
