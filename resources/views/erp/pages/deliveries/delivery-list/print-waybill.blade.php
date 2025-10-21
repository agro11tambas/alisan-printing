<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Surat Jalan - {{ $deliveryList->shipment_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body {
            background: #fff;
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
        }

        pre {
            font-size: 14px;
            white-space: pre;
            margin: 0;
        }
    </style>
</head>

<body>
    <pre id="rawDoc"></pre>

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
            const FIX_LINES = 40;

            const center = t => {
                const pad = Math.floor((width - String(t).length) / 2);
                return ' '.repeat(Math.max(pad, 0)) + t;
            };
            const padR = (s, w) => (String(s) + ' '.repeat(w)).slice(0, w);
            const padL = (s, w) => (' '.repeat(w) + String(s)).slice(-w);

            const wrapText = (text, maxLen) => {
                const words = String(text).split(/\s+/);
                const lines = [];
                let line = '';
                for (const w of words) {
                    if ((line + w).length > maxLen) {
                        lines.push(line.trim());
                        line = w + ' ';
                    } else line += w + ' ';
                }
                if (line.trim() !== '') lines.push(line.trim());
                return lines.slice(0, 2);
            };

            let out = '';
            const totalPages = Math.ceil(items.length / ITEMS_PER_PAGE);

            for (let page = 0; page < totalPages; page++) {
                const start = page * ITEMS_PER_PAGE;
                const end = Math.min(start + ITEMS_PER_PAGE, items.length);
                const pageItems = items.slice(start, end);

                let pageOut = '';

                // ==== JUDUL PALING ATAS ====
                pageOut += center('SURAT JALAN') + CRLF;
                pageOut += center(orderNumber) + CRLF + CRLF;

                // ==== HEADER KIRI (ALISAN) & KANAN (CUSTOMER) ====
                const kiri = [
                    'ALISAN PRINTING',
                    ...wrapText('Jl. Dummy Raya No. 123, Bandung ABC ABC ABC ABC', 30),
                    'Telp: 0812-3456-7890'
                ];
                const kanan = [
                    ...(wrapText(customer.name || '-', 38)),
                    ...(wrapText(customer.address || '-', 38)),
                    customer.phone || '-'
                ];
                const max = Math.max(kiri.length, kanan.length);
                for (let i = 0; i < max; i++) {
                    const left = padR(kiri[i] || '', 45); // kiri sampai kolom 45
                    const rightZoneStart = 60; // kanan mulai kolom ke-50
                    const rightText = kanan[i] || '';
                    const spacing = ' '.repeat(Math.max(0, rightZoneStart - left.length));
                    pageOut += left + spacing + rightText + CRLF;
                }


                // ==== PEMISAH ====
                pageOut += '-'.repeat(width) + CRLF;

                // ==== KOLOM ITEM ====
                pageOut += padR('No', 4) + ' ' + padR('Nama Barang', 55) + ' ' + padR('SKU', 15) + ' ' + padL('Qty', 8) +
                    CRLF;
                pageOut += '-'.repeat(width) + CRLF;

                // ==== DAFTAR BARANG ====
                pageItems.forEach((row) => {
                    const name = String(row.name).substring(0, 37);
                    const sku = String(row.sku).substring(0, 10);
                    pageOut += padR(row.no, 4) + ' ' + padR(name, 55) + ' ' + padR(sku, 15) + ' ' + padL(row.qty,
                        8) + CRLF;
                });

                // ==== FOOTER (TANDA TANGAN) ====
                pageOut += '-'.repeat(width) + CRLF.repeat(2);
                const linesNow = pageOut.split(/\r\n/).length;
                const signBlockLines = 17;
                const remaining = Math.max(0, FIX_LINES - (linesNow + signBlockLines));
                pageOut += CRLF.repeat(remaining);

                const centerLine = t => {
                    const pad = Math.floor((width - t.length) / 2);
                    return ' '.repeat(Math.max(pad, 0)) + t + CRLF;
                };
                pageOut += centerLine('   Admin             Kurir           Customer');
                pageOut += CRLF.repeat(2);
                pageOut += centerLine('______________    ______________    ______________');
                pageOut += CRLF.repeat(2);
                pageOut += center('Halaman ' + (page + 1) + ' dari ' + totalPages);

                out += pageOut;
                if (page + 1 < totalPages) out += CRLF.repeat(5);
            }

            return out;
        }

        document.getElementById('rawDoc').textContent = buildText96();

        // === AUTO DIRECT PRINT SAAT HALAMAN DIBUKA ===
        window.addEventListener('load', async () => {
            try {
                if (!window.qz) throw new Error("QZ Tray tidak aktif. Jalankan QZ Tray terlebih dahulu.");
                if (!qz.websocket.isActive()) await qz.websocket.connect();

                // fungsi hitung feed bawah agar tinggi total 14cm
                function feedToBottom(text, totalHeightMm = 140) {
                    const lines = text.split(/\r\n/).length;
                    const printedHeightMm = lines * 3.5; // asumsi 3.5mm/baris
                    const remainingMm = Math.max(0, totalHeightMm - printedHeightMm);
                    const feedLines = Math.round(remainingMm / 3.5);
                    return '\x1B' + 'd' + String.fromCharCode(feedLines > 255 ? 255 : feedLines);
                }

                const textOut = buildText96();
                const ESC = '\x1B';
                // === SET PAGE LENGTH 5.5 inch (≈14 cm) ===
                // ESC C n → n = jumlah baris, 1 baris = 1/6 inch → 33 baris = 5.5 inch
                const setPageLength = ESC + 'C' + String.fromCharCode(33);

                const payload =
                    ESC + '@' +
                    setPageLength +
                    ESC + 'x' + '\x00' +
                    ESC + 'U' + '\x01' +
                    ESC + 'E' + '\x00' +
                    ESC + 'g' + '\x00' +
                    ESC + 'M' +
                    ESC + 'l' + '\x00' +
                    ESC + 'Q' + '\x00' +
                    ESC + '2' +
                    textOut +
                    feedToBottom(textOut, 140) +
                    ESC + '2';

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
                window.close(); // tutup tab otomatis setelah print
            } catch (e) {
                alert('❌ Gagal print:\n' + e.message);
                console.error(e);
            }
        });
    </script>
</body>

</html>
