@php
    // 🔧 Dipakai untuk ukuran font & preview di layar saja.
    //    Lebar saat CETAK sepenuhnya mengikuti kertas dari driver printer
    //    (lihat @page size:auto + width:100% di bawah), bukan angka mm ini.
    $paperWidth = in_array((int) ($paperWidth ?? 80), [58, 80]) ? (int) $paperWidth : 80;

    // 🔧 Jarak aman tepi KANAN saat cetak (mm).
    //    Kepala printer thermal berhenti beberapa mm sebelum tepi kertas, jadi
    //    kolom qty yang rata kanan ("500 Pcs") kepotong jadi "500 Pc".
    //    Naikkan angka ini kalau masih ada huruf/angka terakhir yang hilang.
    //    Bisa dites cepat lewat query string: ...?right_safe=8
    $rightSafe = min(max((float) request()->input('right_safe', 6), 0), 20);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Daftar Kerja — {{ $machineName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        @page {
            /* 🔧 JANGAN kunci lebar halaman ke mm.
               Kalau ukuran di sini beda dengan kertas yang dipilih di driver
               printer, Chrome tetap menyusun konten selebar deklarasi ini lalu
               sisi kanan dibuang. size:auto = ikut kertas asli dari driver. */
            size: auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            color: #000;
            /* 🔧 Sans-serif tebal jauh lebih tajam di printer thermal 203dpi
               dibanding serif/monospace tipis */
            font-family: Arial, Helvetica, sans-serif;
            -webkit-font-smoothing: none;
            text-rendering: geometricPrecision;
        }

        body {
            background: #f3f4f6;
        }

        .receipt {
            width: {{ $paperWidth }}mm;
            margin: 8px auto;
            padding: 0 1mm;
            background: #fff;
            font-size: {{ $paperWidth === 58 ? '11px' : '12px' }};
            line-height: 1.3;
            font-weight: 700;
        }

        @media screen {

            /* Preview di layar saja, tidak berpengaruh ke hasil cetak. */
            .receipt {
                box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
            }
        }

        .center {
            text-align: center;
        }

        .machine-name {
            font-size: {{ $paperWidth === 58 ? '15px' : '18px' }};
            font-weight: 900;
            text-transform: uppercase;
        }

        .sep {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }

        .sep-solid {
            border-top: 2px solid #000;
            margin: 5px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 4px;
        }

        .row .qty {
            white-space: nowrap;
            flex: 0 0 auto;
        }

        .product {
            /* 🔧 min-width:0 wajib, kalau tidak flex item menolak menyusut dan
               nama produk panjang mendorong qty keluar dari area cetak */
            min-width: 0;
            flex: 1 1 auto;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .note {
            padding-left: 6px;
            font-weight: 400;
        }

        .customer {
            text-transform: uppercase;
            word-break: break-word;
        }

        .muted {
            font-size: {{ $paperWidth === 58 ? '10px' : '11px' }};
            font-weight: 400;
        }

        .total {
            font-size: {{ $paperWidth === 58 ? '13px' : '14px' }};
            font-weight: 900;
        }

        .toolbar {
            text-align: center;
            padding: 8px;
            font-family: Arial, sans-serif;
        }

        .toolbar button {
            padding: 6px 12px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
        }

        @media print {

            html,
            body {
                background: #fff;
                /* Ikut lebar kertas dari driver, bukan angka mm hardcode */
                width: 100%;
                max-width: 100%;
                overflow-x: hidden;
            }

            .receipt {
                /* 🔧 Lebar ikut kertas, tapi sisi kanan ditarik masuk
                   {{ $rightSafe }}mm supaya kolom qty tidak masuk area yang
                   tidak terjangkau kepala printer. Kiri tetap 0 karena di sana
                   sudah ada margin fisik dari printer. */
                margin: 0;
                padding: 0 {{ $rightSafe }}mm 0 0;
                width: 100%;
                max-width: 100%;
                box-shadow: none;
            }

            .toolbar {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨️ Print</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="receipt">
        <div class="center">
            <div class="machine-name">{{ $machineName }}</div>
            <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="sep-solid"></div>

        @forelse ($groups as $group)
            @php
                // 🔧 Satu blok = satu customer: nama customer + akun pemesan
                //    (nama - nomor WhatsApp), lalu note dari sale list.
                //    Alamat & nomor invoice sengaja tidak dicetak.
                $order = $group['order'];
                $customerName = $order?->customer?->name;
                $account = $order?->customerAccount;
                $accountWhatsapp = \App\Support\PhoneNumber::toLocalIndonesian($account?->whatsapp_number);
                $accountLabel = collect([$account?->name, $accountWhatsapp])
                    ->map(fn($value) => trim((string) $value))
                    ->filter()
                    ->implode(' - ');
                $orderNotes = trim((string) ($order?->notes ?? ''));
            @endphp

            <div class="customer">{{ $customerName ?? '-' }}</div>
            @if ($accountLabel !== '')
                <div class="muted">{{ $accountLabel }}</div>
            @endif
            @if ($orderNotes !== '')
                <div class="muted">Note: {{ $orderNotes }}</div>
            @endif

            <div class="sep"></div>

            {{-- 🔧 produk yang sama sudah digabung --}}
            @foreach ($group['lines'] as $line)
                <div class="row">
                    <div class="product">{{ $line['product'] }}</div>
                    <div class="qty">{{ number_format($line['qty'], 0, ',', '.') }} {{ $line['unit'] }}</div>
                </div>
                @if ($line['note'])
                    <div class="note muted">- {{ $line['note'] }}</div>
                @endif
            @endforeach

            <div class="sep-solid"></div>
        @empty
            <div class="center">Tidak ada assign untuk mesin ini.</div>
            <div class="sep-solid"></div>
        @endforelse

        {{-- 🔧 Total qty semua produk, produk secondary di bundle tidak dihitung --}}
        <div class="row total">
            <div>TOTAL QTY</div>
            <div>{{ number_format($totalQty, 0, ',', '.') }}</div>
        </div>

        <div class="sep"></div>
        <div class="center muted">--- {{ $machineName }} ---</div>
    </div>

    <script>
        // 🔧 Begitu halaman terbuka → langsung panggil print.
        //    Kalau Chrome dijalankan dengan flag --kiosk-printing, dialog print
        //    TIDAK muncul dan struk langsung keluar di printer default.
        window.addEventListener('load', function() {
            window.print();
        });

        // Tab ditutup otomatis setelah proses cetak selesai
        window.addEventListener('afterprint', function() {
            setTimeout(function() {
                window.close();
            }, 300);
        });
    </script>
</body>

</html>
