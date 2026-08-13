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
            font-family: 'Courier New', Courier, monospace;
        }

        body {
            background: #f3f4f6;
        }

        .receipt {
            width: {{ $paperWidth }}mm;
            margin: 8px auto;
            padding: 3mm;
            background: #fff;
            font-size: {{ $paperWidth === 58 ? '10px' : '11px' }};
            line-height: 1.35;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .machine-name {
            font-size: {{ $paperWidth === 58 ? '14px' : '16px' }};
            font-weight: bold;
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
            font-weight: bold;
        }

        .product {
            word-break: break-word;
        }

        .note {
            font-style: italic;
            padding-left: 6px;
        }

        .customer {
            font-weight: bold;
            text-transform: uppercase;
            word-break: break-word;
        }

        .muted {
            font-size: {{ $paperWidth === 58 ? '9px' : '10px' }};
        }

        .noprint {
            text-align: center;
            padding: 8px;
        }

        .noprint button {
            padding: 6px 12px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
        }

        @media print {
            body {
                background: #fff;
            }

            .receipt {
                margin: 0;
                width: auto;
            }

            .noprint {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="noprint">
        <button onclick="window.print()">🖨️ Print Ulang</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="receipt">
        <div class="center">
            <div class="bold">DAFTAR KERJA PRODUKSI</div>
            <div class="machine-name">{{ $machineName }}</div>
            <div class="muted">{{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="sep-solid"></div>

        @forelse ($groups as $group)
            @php
                // 🔧 Satu blok = satu customer: nama + kontak, tanpa nomor invoice
                $order = $group['order'];
                $customerName = $order?->customer?->name;
                $contact = \App\Support\PhoneNumber::toLocalIndonesian($order?->order_whatsapp_number);
            @endphp

            <div class="customer">{{ $customerName ?? '-' }}</div>
            <div class="muted">{{ $contact ?: '-' }}</div>

            <div class="sep"></div>

            @foreach ($group['assigns'] as $assign)
                @php
                    $conversion = max((float) ($assign->progressItem?->unit_conversion_value ?? 1), 1);
                    $qty = number_format($assign->assigned_quantity / $conversion, 0, ',', '.');
                @endphp
                <div class="row">
                    <div class="product">{{ $assign->progressItem?->product?->name ?? '-' }}</div>
                    <div class="qty">{{ $qty }} {{ $assign->progressItem?->unit_name }}</div>
                </div>
                @if ($assign->note)
                    <div class="note muted">- {{ $assign->note }}</div>
                @endif
            @endforeach

            <div class="sep-solid"></div>
        @empty
            <div class="center">Tidak ada assign untuk mesin ini.</div>
            <div class="sep-solid"></div>
        @endforelse

        {{-- 🔧 Total qty semua produk, produk secondary di bundle tidak dihitung --}}
        <div class="row">
            <div class="bold">TOTAL QTY</div>
            <div class="bold">{{ number_format($totalQty, 0, ',', '.') }}</div>
        </div>

        <div class="sep"></div>
        <div class="center muted">--- {{ $machineName }} ---</div>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>

</html>
