@php
    $paperWidth = in_array((int) ($paperWidth ?? 80), [58, 80]) ? (int) $paperWidth : 80;
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Daftar Kerja — {{ $machineName }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        @page {
            size: {{ $paperWidth }}mm auto;
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
            padding: 2mm;
            background: #fff;
            font-size: {{ $paperWidth === 58 ? '12px' : '13px' }};
            line-height: 1.3;
            font-weight: 700;
        }

        .center {
            text-align: center;
        }

        .machine-name {
            font-size: {{ $paperWidth === 58 ? '17px' : '20px' }};
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
        }

        .product {
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
            font-size: {{ $paperWidth === 58 ? '11px' : '12px' }};
            font-weight: 400;
        }

        .total {
            font-size: {{ $paperWidth === 58 ? '14px' : '16px' }};
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
            body {
                background: #fff;
            }

            .receipt {
                margin: 0;
                width: auto;
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
            <div>DAFTAR KERJA PRODUKSI</div>
            <div class="machine-name">{{ $machineName }}</div>
            <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="sep-solid"></div>

        @forelse ($groups as $group)
            @php
                // 🔧 Satu blok = satu customer: nama + kontak + alamat, tanpa nomor invoice
                $order = $group['order'];
                $customerName = $order?->customer?->name;
                $contact = \App\Support\PhoneNumber::toLocalIndonesian($order?->order_whatsapp_number);
                $address = $order?->customerAddress?->address;
                $businessName = $order?->customerAddress?->business_name;
            @endphp

            <div class="customer">{{ $customerName ?? '-' }}</div>
            <div class="muted">{{ $contact ?: '-' }}</div>
            @if ($businessName)
                <div class="muted">{{ $businessName }}</div>
            @endif
            @if ($address)
                <div class="muted">{{ $address }}</div>
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
