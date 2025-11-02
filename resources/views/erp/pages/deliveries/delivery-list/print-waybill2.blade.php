<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Surat Jalan - {{ $deliveryList->shipment_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body {
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .sheet {
            background: #fff;
            margin: 0px auto;
            box-shadow: 0 2mm 6mm rgba(0, 0, 0, .08);
            padding: 30px;
            width: 21cm;
            height: 14cm;
        }

        pre {
            font: 14px/1.25 'Courier New', monospace;
            white-space: pre;
            margin: 0;
        }

        .noprint {
            text-align: center;
            padding: 10px;
        }

        @media print {
            body {
                background: #fff;
            }

            .sheet {
                margin: 0px;
                box-shadow: none;
            }

            .noprint {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <div class="noprint">
        <button onclick="window.print()"
            style="padding:8px 12px; border:1px solid #ccc; background:#fff; border-radius:6px;">🖨️ Print
            (Browser)</button>
        <button id="btnRawPrint" style="padding:8px 12px; border:1px solid #ccc; background:#fff; border-radius:6px;">⚡
            Cetak Direct (RAW LX-310)</button>
    </div>

    <div class="sheet">
        <pre id="rawDoc"></pre>
    </div>

    @php
        $itemsJs = $deliveryList->items
            ->values()
            ->map(function ($item, $i) {
                return [
                    'no' => $i + 1,
                    'name' => $item->product->name ?? '-',
                    'sku' => $item->product->sku ?? '-',
                    'qty' => (string) ($item->shipped_quantity ?? 0),
                ];
            })
            ->all();

        $customerJs = [
            'name' => $deliveryList->deliveryOrder->order->customer->name ?? '-',
            'address' => $deliveryList->deliveryOrder->shipping_address ?? '-',
            'phone' => $deliveryList->deliveryOrder->order->customer->phone ?? '-',
        ];
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script>
    <script>
        const orderNumber = @json($deliveryList->shipment_number);
        const orderDate = @json(\Carbon\Carbon::parse($deliveryList->shipment_date)->format('d-m-Y'));
        const items = @json($itemsJs);
        const customer = @json($customerJs);

        const padR = (s, w) => (String(s) + ' '.repeat(w)).slice(0, w);
        const padL = (s, w) => (' '.repeat(w) + String(s)).slice(-w);
        const line4 = (a, b, c, d) => padR(a, 4) + ' ' + padR(b, 55) + ' ' + padR(c, 15) + ' ' + padL(d, 8);

        function buildText96() {
            const width = 96,
                CRLF = "\r\n";
            const ITEMS_PER_PAGE = 10;
            const FIX_LINES = 40; // fix tinggi tiap halaman (~14cm)
            const center = t => ' '.repeat(Math.max(0, Math.floor((width - String(t).length) / 2))) + t;

            let out = '';
            const totalPages = Math.ceil(items.length / ITEMS_PER_PAGE);

            for (let page = 0; page < totalPages; page++) {
                // ambil data per halaman
                const start = page * ITEMS_PER_PAGE;
                const end = Math.min(start + ITEMS_PER_PAGE, items.length);
                const pageItems = items.slice(start, end);

                let pageOut = '';

                // ===== HEADER =====
                pageOut += center('SURAT JALAN') + CRLF + CRLF;
                pageOut += padR('No Surat : ' + orderNumber, 35) +
                    padR('', 30) +
                    padR('Customer : ' + (customer.name || '-'), 31) + CRLF;
                pageOut += padR('Tanggal : ' + orderDate, 35) +
                    padR('', 30) +
                    padR('Alamat   : ' + (customer.address || '-'), 31) + CRLF;
                pageOut += padR('', 35) +
                    padR('', 30) +
                    padR('Telp     : ' + (customer.phone || '-'), 31) + CRLF;
                pageOut += '-'.repeat(width) + CRLF;
                pageOut += line4('No', 'Nama Barang', 'SKU', 'Qty') + CRLF;
                pageOut += '-'.repeat(width) + CRLF;

                // ===== BARANG =====
                pageItems.forEach((row) => {
                    const name = String(row.name).substring(0, 37);
                    const sku = String(row.sku).substring(0, 10);
                    pageOut += line4(row.no, name, sku, row.qty) + CRLF;
                });

                pageOut += '-'.repeat(width) + CRLF.repeat(2);

                // ===== HITUNG RUANG UNTUK BLOK TANDA TANGAN =====
                const linesNow = pageOut.split(/\r\n/).length;
                const signBlockLines = 16; // tinggi blok tanda tangan
                const remaining = Math.max(0, FIX_LINES - (linesNow + signBlockLines));
                pageOut += CRLF.repeat(remaining);

                // ===== BLOK TANDA TANGAN =====
                const centerLine = (text) => {
                    const pad = Math.floor((width - text.length) / 2);
                    return ' '.repeat(Math.max(pad, 0)) + text + CRLF;
                };

                pageOut += centerLine('   Admin             Kurir           Customer');
                pageOut += CRLF.repeat(2);
                pageOut += centerLine('______________    ______________    ______________');
                pageOut += CRLF.repeat(2);
                pageOut += center('Halaman ' + (page + 1) + ' dari ' + totalPages);

                // ===== GABUNGKAN KE OUT DAN KASIH SPASI ANTAR HALAMAN =====
                out += pageOut;
                if (page + 1 < totalPages) out += CRLF.repeat(5); // jeda antar halaman
            }

            return out;
        }


        document.getElementById('rawDoc').textContent = buildText96();

        if (window.qz) {
            if (window.crypto && crypto.subtle) {
                qz.api.setSha256Type(d => crypto.subtle.digest("SHA-256", new TextEncoder().encode(d)));
            } else qz.api.setSha256Type(d => d);
            qz.api.setPromiseType(fn => new Promise(fn));
        }

        async function connectQZ() {
            if (!window.qz) throw new Error("QZ Tray belum aktif");
            if (!qz.websocket.isActive()) await qz.websocket.connect();
        }

        async function rawPrint() {
            try {
                await connectQZ();
                const ESC = '\x1B';
                const payload =
                    ESC + '@' +
                    ESC + 'x' + '\x00' +
                    ESC + 'U' + '\x01' +
                    ESC + 'E' + '\x01' +
                    ESC + 'l' + '\x00' +
                    ESC + '$' + '\xD0' + '\xFF' +
                    ESC + 'Q' + '\x50' +
                    ESC + 'M' +
                    ESC + '2' + // normal line spacing
                    buildText96();

                const config = qz.configs.create("EPSON LX-310", {
                    encoding: "CP437",
                    altPrinting: false
                });

                const data = [{
                    type: 'raw',
                    format: 'command',
                    data: payload
                }];
                await qz.print(config, data);
                alert('✅ Dikirim ke LX-310 (80 kolom, margin fix)');
            } catch (e) {
                alert('❌ Gagal print:\n' + e.message);
            }
        }

        document.getElementById('btnRawPrint').addEventListener('click', rawPrint);
    </script>
</body>

</html>
