{{--
    Modal opsi periode export Excel. Dipakai bersama oleh Sale List dan
    Purchase Order.

    Parameter include:
    - $exportUrl   : path endpoint export. Sengaja relatif, bukan url(), supaya
                     ikut skema halaman (absolute http:// diblokir browser
                     sebagai insecure download saat halaman https).
    - $exportYears : array tahun untuk dropdown, urut dari yang terbaru.

    Halaman pemanggil wajib:
    - memberi class .js-open-export-modal pada tombol export-nya;
    - menyediakan window.buildExportParams() yang mengembalikan object berisi
      filter halaman yang sedang aktif.
--}}
@php
    $exportMonths = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];
@endphp

@push('modals')
    <div class="modal fade" id="modalExportExcel" tabindex="-1" aria-labelledby="modalExportExcelLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalExportExcelLabel">Export Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="export_period" class="fw-semibold fs-12">Periode</label>
                        <select id="export_period" class="form-control">
                            <option value="current">Ikuti Filter Halaman</option>
                            <option value="month">Per Bulan</option>
                            <option value="year">Per Tahun</option>
                            <option value="range">Rentang Tanggal</option>
                        </select>
                    </div>

                    <div class="row g-2 mt-2">
                        <div class="col-7 export-period-field d-none" data-period="month">
                            <label for="export_month" class="fw-semibold fs-12">Bulan</label>
                            <select id="export_month" class="form-control">
                                @foreach ($exportMonths as $monthNumber => $monthName)
                                    <option value="{{ $monthNumber }}" @selected($monthNumber === now()->month)>
                                        {{ $monthName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-5 export-period-field d-none" data-period="month year">
                            <label for="export_year" class="fw-semibold fs-12">Tahun</label>
                            <select id="export_year" class="form-control">
                                @foreach ($exportYears as $year)
                                    <option value="{{ $year }}" @selected($year === now()->year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 export-period-field d-none" data-period="range">
                            <label for="export_start_date" class="fw-semibold fs-12">Dari Tanggal</label>
                            <input type="date" id="export_start_date" class="form-control">
                        </div>

                        <div class="col-6 export-period-field d-none" data-period="range">
                            <label for="export_end_date" class="fw-semibold fs-12">Sampai Tanggal</label>
                            <input type="date" id="export_end_date" class="form-control">
                        </div>
                    </div>

                    <p class="fs-11 text-muted mb-0 mt-3">
                        Filter lain yang sedang aktif di halaman (pencarian, status) tetap ikut terpakai.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-md" data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btnSubmitExportExcel" class="btn btn-primary btn-md">
                        <i class="feather-download me-2"></i>Download
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(function() {
            const $modalExportExcel = $('#modalExportExcel');

            // Tampilkan hanya field yang relevan dengan periode terpilih.
            function syncExportPeriodFields() {
                const period = $('#export_period').val();

                $('.export-period-field').each(function() {
                    const shownOn = String($(this).data('period')).split(' ');
                    $(this).toggleClass('d-none', !shownOn.includes(period));
                });
            }

            $('#export_period').on('change', syncExportPeriodFields);
            syncExportPeriodFields();

            $(document).on('click', '.js-open-export-modal', function() {
                $modalExportExcel.modal('show');
            });

            $('#btnSubmitExportExcel').on('click', function() {
                const period = $('#export_period').val();
                const params = typeof window.buildExportParams === 'function' ?
                    window.buildExportParams() :
                    {};

                params.export_period = period;

                if (period === 'month') {
                    params.export_month = $('#export_month').val();
                    params.export_year = $('#export_year').val();
                } else if (period === 'year') {
                    params.export_year = $('#export_year').val();
                } else if (period === 'range') {
                    const startDate = $('#export_start_date').val();
                    const endDate = $('#export_end_date').val();

                    if (!startDate || !endDate) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tanggal Belum Lengkap',
                            text: 'Isi tanggal awal dan tanggal akhir dulu.',
                        });
                        return;
                    }

                    params.export_start_date = startDate;
                    params.export_end_date = endDate;
                }

                $modalExportExcel.modal('hide');

                window.location.href = @json($exportUrl) + '?' + $.param(params);
            });
        });
    </script>
@endpush
