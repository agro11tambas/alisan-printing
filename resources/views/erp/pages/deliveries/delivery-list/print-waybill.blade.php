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
            padding: 5px;
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
            ->groupBy(fn($item) => $item->product->name ?? '-')
            ->map(function ($group, $name) {
                $totalQty = $group->sum('shipped_quantity');

                // Gabungkan semua note berdasarkan product_id
                $notes = $group->pluck('note')->filter()->unique()->implode(', ');

                return [
                    'name' => $name,
                    'note' => $notes ?: '-',
                    'qty' => (string) $totalQty,
                    'unit_name' => $unitName ?: '-',
                ];
            })
            ->values()
            ->map(function ($item, $i) {
                $item['no'] = $i + 1;
                return $item;
            })
            ->all();

        $customerJs = [
            'name' => $deliveryList->deliveryOrder->order->business_name ?? '-',
            'address' => $deliveryList->deliveryOrder->shipping_address ?? '-',
            'phone' => $deliveryList->deliveryOrder->order->customer->phone ?? '-',
            'note' => $deliveryList->note ?? '-',
            'order_note' => $deliveryList->deliveryOrder->order->notes ?? '-',
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
            const ITEMS_PER_PAGE = 5;
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
                return lines.slice(0, 4);
            };

            let out = '';
            const totalPages = Math.ceil(items.length / ITEMS_PER_PAGE);

            for (let page = 0; page < totalPages; page++) {
                const start = page * ITEMS_PER_PAGE;
                const end = Math.min(start + ITEMS_PER_PAGE, items.length);
                const pageItems = items.slice(start, end);

                let pageOut = '';

                pageOut += center('SURAT JALAN') + CRLF;
                pageOut += center(orderNumber) + CRLF + CRLF;

                // KIRI = Customer
                const kiri = [
                    ...(wrapText(customer.name || '-', 50)),
                    ...(wrapText(customer.address || '-', 50)),
                    customer.phone || '-',
                    ...(customer.order_note ? wrapText('Catatan Order: ' + customer.order_note, 50) : []),
                    customer.note ? 'Catatan: ' + customer.note : ''
                ];

                // KANAN = Alisan Printing
                const kanan = [
                    'ALISAN PRINTING',
                    ...wrapText('Jl. Karya Indah No 32', 30),
                    '082272722188'
                ];

                const max = Math.max(kiri.length, kanan.length);
                for (let i = 0; i < max; i++) {
                    const left = padR(kiri[i] || '', 55);
                    const rightZoneStart = 70;
                    const rightText = kanan[i] || '';
                    const spacing = ' '.repeat(Math.max(0, rightZoneStart - left.length));
                    pageOut += left + spacing + rightText + CRLF;
                }

                pageOut += '-'.repeat(width) + CRLF;

                pageOut += padR('No', 4) + ' ' + padR('Nama Barang', 55) + ' ' + padR('Qty', 8) + ' ' + padR('Unit', 8) +
                    ' ' + padL('Catatan', 13) + CRLF;
                pageOut += '-'.repeat(width) + CRLF;

                pageItems.forEach((row) => {
                    const name = String(row.name).substring(0, 53);
                    const note = String(row.note || '-').substring(0, 22);
                    const unitName = String(row.unit_name || '-').substring(0, 8);
                    const qtyFormatted = Number(row.qty).toLocaleString('id-ID'); // ✅ format angka Indonesia
                    pageOut += padR(row.no, 4) + ' ' +
                        padR(name, 55) + ' ' +
                        padR(qtyFormatted, 8) + ' ' +
                        padR(unitName, 8) + ' ' +
                        padL(note, 13) + CRLF;
                });

                pageOut += '-'.repeat(width) + CRLF.repeat(2);

                // if (customer.order_note) {
                //     const wrapped = wrapText(customer.order_note, 70); // 70 biar muat sebelahnya

                //     pageOut += 'Catatan Order : ' + wrapped[0] + CRLF;

                //     for (let i = 1; i < wrapped.length; i++) {
                //         pageOut += ' '.repeat(17) + wrapped[i] + CRLF;
                //     }

                //     pageOut += CRLF;
                // }

                const linesNow = pageOut.split(/\r\n/).length;
                const signBlockLines = 20;
                const remaining = Math.max(0, FIX_LINES - (linesNow + signBlockLines));
                pageOut += CRLF.repeat(remaining);

                const centerLine = t => {
                    const pad = Math.floor((width - t.length) / 2);
                    return ' '.repeat(Math.max(pad, 0)) + t + CRLF;
                };
                pageOut += centerLine('    Admin                         Kurir                       Customer   ');
                pageOut += CRLF.repeat(2);
                pageOut += centerLine('______________                ______________               ______________');
                pageOut += CRLF.repeat(1);
                // pageOut += center('Halaman ' + (page + 1) + ' dari ' + totalPages);
                const pageText = 'Hal ' + (page + 1) + '/' + totalPages;
                pageOut += center(pageText) + CRLF;

                out += pageOut;
                if (page + 1 < totalPages) out += CRLF.repeat(5);
            }

            return out;
        }

        document.getElementById('rawDoc').textContent = buildText96();

        window.addEventListener('load', async () => {
            try {
                if (!window.qz) throw new Error("QZ Tray tidak aktif. Jalankan QZ Tray terlebih dahulu.");
                if (!qz.websocket.isActive()) await qz.websocket.connect();

                function feedToBottom(text, totalHeightMm = 140) {
                    const lines = text.split(/\r\n/).length;
                    const printedHeightMm = lines * 3.5;
                    const remainingMm = Math.max(0, totalHeightMm - printedHeightMm);
                    const feedLines = Math.round(remainingMm / 3.5);
                    return '\x1B' + 'd' + String.fromCharCode(feedLines > 255 ? 255 : feedLines);
                }

                const textOut = buildText96();
                const ESC = '\x1B';

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
                window.close();
            } catch (e) {
                alert('Gagal print:\n' + e.message);
            }
        });
    </script>
</body>

</html>
