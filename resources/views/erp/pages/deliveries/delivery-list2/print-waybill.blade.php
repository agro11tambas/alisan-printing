<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Surat Jalan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        :root {
            --paper: a4;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
            color: #111;
        }

        body {
            background: #f3f4f6;
        }

        .sheet {
            background: #fff;
            margin: 2px auto;
            box-shadow: 0 2mm 6mm rgba(0, 0, 0, .08);
            overflow: visible;
        }

        .sheet.size-a4 {
            width: 21cm;
            height: 14cm;
            padding: 6mm 6mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .sheet {
                margin: 0;
                box-shadow: none;
                width: 100%;
                height: 14cm;
                padding: 8mm;
            }

            .noprint {
                display: none !important;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="noprint" style="text-align:center; padding: 5px;">
        <button onclick="window.print()" style="padding: 4px 8px; border:1px solid #ccc; background:#fff; cursor:pointer; border-radius:6px;">
            🖨️ Print (Browser)
        </button>
        <button id="btnRawPrint" style="padding: 4px 8px; border:1px solid #ccc; background:#fff; cursor:pointer; border-radius:6px;">
            ⚡ Cetak Direct (RAW LX-310)
        </button>
    </div>

    <div class="sheet size-a4">
        <pre id="rawDoc" style="font:14px/1.25 'Courier New', monospace; white-space:pre; margin:0;"></pre>
    </div>

    <script>
        (function() {
            const s = document.querySelector('.sheet');
            if (getComputedStyle(document.documentElement).getPropertyValue('--paper').trim() === 'a4') {
                s.classList.remove('size-a5');
                s.classList.add('size-a4');
            }
        })();
    </script>

    @php
    $itemsJs = $order->orderItems
    ->values()
    ->map(function($item, $i) {
    return [
    'no' => $i + 1,
    'name' => $item->product->name ?? '-',
    'sku' => $item->product->sku ?? '-',
    'qty' => (string)($item->quantity ?? 0),
    ];
    })->all();

    $customerJs = [
    'name' => $order->customer->name ?? ($order->customer_name ?? '-'),
    'address' => $order->shipping_address ?? ($order->customer_addr ?? '-'),
    'phone' => \App\Support\PhoneNumber::toLocalIndonesian($order->order_whatsapp_number ?? $order->customer_phone) ?? '-',
    ];
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script>
    <script>
        // ===== DATA DARI BLADE =====
        const orderNumber = @json($order->order_number);
        const orderDate = @json(now()->format('d-m-Y'));
        const items = @json($itemsJs);
        const customer = @json($customerJs);

        // ===== UTIL =====
        const padR = (s, w) => (String(s) + ' '.repeat(w)).slice(0, w);
        const padL = (s, w) => (' '.repeat(w) + String(s)).slice(-w);
        const line4 = (a, b, c, d) => padR(a, 4) + ' ' + padR(b, 45) + ' ' + padR(c, 15) + ' ' + padL(d, 8);

        // ===== SUMBER TUNGGAL: TEKS 80 KOLOM =====
        function buildText80() {
            const width = 88,
                CRLF = "\r\n";

            // Header 3 kolom: 30 | 2 | 48 = 80
            const COL1 = 30,
                COL2 = 28,
                COL3 = 30;

            const PAGE_LINES = 30; // 21x14 cm dgn padding → ~30 baris (atur 27–31 sesuai hasil)
            const SIGN_BLOCK_LINES = 7; // tinggi blok tanda tangan

            const center = t => ' '.repeat(Math.max(0, Math.floor((width - String(t).length) / 2))) + t;

            let out = '';
            // Title
            out += center('SURAT JALAN') + CRLF + CRLF;

            // ===== HEADER 3 KOLOM =====
            out += padR('Invoice : ' + orderNumber, COL1) +
                padR('', COL2) +
                padR('Customer : ' + (customer.name || '-'), COL3) + CRLF;

            out += padR('Tanggal : ' + orderDate, COL1) +
                padR('', COL2) +
                padR('Alamat   : ' + (customer.address || '-'), COL3) + CRLF;

            out += padR('', COL1) +
                padR('', COL2) +
                padR('Telp     : ' + (customer.phone || '-'), COL3) + CRLF;

            out += '-'.repeat(width) + CRLF;

            // ===== TABEL =====
            out += line4('No', 'Nama Barang', 'Kode', 'Qty') + CRLF;
            out += '-'.repeat(width) + CRLF;

            items.forEach(row => {
                const name = String(row.name).substring(0, 45);
                const sku = String(row.sku).substring(0, 15);
                out += line4(row.no, name, sku, row.qty) + CRLF;
            });

            out += '-'.repeat(width) + CRLF;

            // ===== Tempel tanda tangan ke bawah halaman =====
            const linesNow = out.split(CRLF).length;
            const minBeforeSign = PAGE_LINES - SIGN_BLOCK_LINES;
            if (linesNow < minBeforeSign) out += '\r\n'.repeat(minBeforeSign - linesNow);

            // ===== TANDA TANGAN (3 kolom) =====
            out += padR('Admin', 20) + padR('Kurir', 20) + padR('Customer', 20) + CRLF;
            out += padR('Nama: _______', 20) + padR('Nama: _______', 20) + padR('Nama: _______', 20) + CRLF;
            out += padR('Tgl : ____-__-__', 20) + padR('Tgl : ____-__-__', 20) + padR('Tgl : ____-__-__', 20) + CRLF + CRLF;

            return out;
        }

        // Preview (sama persis dengan yang akan dicetak)
        document.getElementById('rawDoc').textContent = buildText80();
        window.addEventListener('resize', () => {
            document.getElementById('rawDoc').textContent = buildText80();
        });

        // ===== CETAK RAW VIA QZ =====
        if (window.qz) {
            if (window.crypto && crypto.subtle) {
                qz.api.setSha256Type(d => crypto.subtle.digest("SHA-256", new TextEncoder().encode(d)));
            } else {
                qz.api.setSha256Type(d => d);
            }
            qz.api.setPromiseType(fn => new Promise(fn));
        }

        async function connectQZ() {
            if (!window.qz) throw new Error("QZ Tray library belum dimuat.");
            if (!qz.websocket.isActive()) await qz.websocket.connect();
        }

        async function rawPrint() {
            try {
                await connectQZ();
                const ESC = '\x1B';
                const payload = ESC + '@' + ESC + 'P' + buildText80(); // init + 10cpi + teks
                const config = qz.configs.create("EPSON LX-310", {
                    encoding: "CP437",
                    altPrinting: true
                });
                const data = [{
                    type: 'raw',
                    format: 'command',
                    data: payload
                }];
                await qz.print(config, data);
                alert('Dikirim ke LX-310 ✅');
            } catch (e) {
                alert('Gagal kirim ke printer.\nPastikan QZ Tray aktif & nama printer benar.\n' + e.message);
            }
        }
        document.getElementById('btnRawPrint').addEventListener('click', rawPrint);
    </script>
</body>

</html>
