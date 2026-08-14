@php
    $paperWidth = in_array((int) ($paperWidth ?? 80), [58, 80]) ? (int) $paperWidth : 80;

    // 🔧 Data untuk cetak RAW (ESC/POS) lewat QZ Tray — dicetak pakai font internal
    //    printer, jadi hasilnya tajam (tidak buram seperti render browser).
    $printData = [
        'machine' => $machineName,
        'printed_at' => now()->format('d/m/Y H:i'),
        'total_qty' => (float) $totalQty,
        'groups' => $groups
            ->map(function ($group) {
                $order = $group['order'];

                return [
                    'customer' => $order?->customer?->name ?? '-',
                    'contact' => \App\Support\PhoneNumber::toLocalIndonesian($order?->order_whatsapp_number) ?: '-',
                    'branch' => $order?->customerAddress?->business_name,
                    'address' => $order?->customerAddress?->address,
                    'lines' => collect($group['lines'])
                        ->map(
                            fn($line) => [
                                'product' => $line['product'],
                                'qty' => (float) $line['qty'],
                                'unit' => $line['unit'],
                                'note' => $line['note'],
                            ],
                        )
                        ->values(),
                ];
            })
            ->values(),
    ];
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

        .toolbar button,
        .toolbar select {
            padding: 6px 12px;
            border: 1px solid #ccc;
            background: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            font-size: 13px;
        }

        .toolbar #printStatus {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #555;
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
        <select id="printerSelect" title="Pilih printer thermal">
            <option value="">— memuat printer —</option>
        </select>
        <button id="btnDirectPrint">🖨️ Cetak Langsung</button>
        <button onclick="window.print()">Print via Browser</button>
        <button onclick="window.close()">Tutup</button>
        <span id="printStatus"></span>
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

    <script src="https://cdn.jsdelivr.net/npm/qz-tray/qz-tray.js"></script>
    <script>
        // ===== DATA DARI BLADE =====
        const PRINT_DATA = @json($printData);
        const PAPER_WIDTH = {{ $paperWidth }};
        // 80mm font A = 48 kolom, 58mm font A = 32 kolom
        const COLS = PAPER_WIDTH === 58 ? 32 : 48;

        const STORAGE_KEY = 'thermal_printer_name';
        const statusEl = document.getElementById('printStatus');
        const printerSelect = document.getElementById('printerSelect');

        function setStatus(text) {
            statusEl.textContent = text;
        }

        // ===== UTIL TEKS =====
        const padR = (s, w) => (String(s) + ' '.repeat(w)).slice(0, w);
        const padL = (s, w) => (' '.repeat(w) + String(s)).slice(-w);
        const center = (t) => {
            const s = String(t).substring(0, COLS);
            const pad = Math.max(0, Math.floor((COLS - s.length) / 2));
            return ' '.repeat(pad) + s;
        };
        const nf = (n) => new Intl.NumberFormat('id-ID').format(Math.round(n));

        // Produk kiri, qty kanan. Nama produk yang kepanjangan dipotong ke baris berikut.
        function lineProductQty(product, qty) {
            const right = ' ' + qty;
            const leftWidth = COLS - right.length;
            const words = String(product).split(' ');
            const rows = [];
            let current = '';

            words.forEach((word) => {
                const candidate = current ? current + ' ' + word : word;
                if (candidate.length <= leftWidth) {
                    current = candidate;
                } else {
                    if (current) rows.push(current);
                    current = word.substring(0, leftWidth);
                }
            });
            if (current) rows.push(current);

            const out = [];
            rows.forEach((row, i) => {
                if (i === rows.length - 1) {
                    out.push(padR(row, leftWidth) + right);
                } else {
                    out.push(row);
                }
            });

            return out;
        }

        function wrap(text, width) {
            const words = String(text).split(' ');
            const rows = [];
            let current = '';

            words.forEach((word) => {
                const candidate = current ? current + ' ' + word : word;
                if (candidate.length <= width) {
                    current = candidate;
                } else {
                    if (current) rows.push(current);
                    current = word.substring(0, width);
                }
            });
            if (current) rows.push(current);

            return rows;
        }

        // ===== ESC/POS =====
        const ESC = '\x1B';
        const GS = '\x1D';
        const CRLF = '\r\n';

        const init = ESC + '@';
        const alignLeft = ESC + 'a' + '\x00';
        const alignCenter = ESC + 'a' + '\x01';
        const boldOn = ESC + 'E' + '\x01';
        const boldOff = ESC + 'E' + '\x00';
        const sizeNormal = GS + '!' + '\x00';
        const sizeDouble = GS + '!' + '\x11'; // dobel tinggi + lebar
        const sizeTall = GS + '!' + '\x01'; // dobel tinggi
        const cut = GS + 'V' + '\x42' + '\x00';

        function buildEscPos() {
            let out = init + alignCenter;

            out += boldOn + 'DAFTAR KERJA PRODUKSI' + CRLF + boldOff;
            out += sizeDouble + boldOn + String(PRINT_DATA.machine).toUpperCase() + CRLF + boldOff + sizeNormal;
            out += PRINT_DATA.printed_at + CRLF;
            out += alignLeft + '='.repeat(COLS) + CRLF;

            if (!PRINT_DATA.groups.length) {
                out += center('Tidak ada assign untuk mesin ini.') + CRLF;
                out += '='.repeat(COLS) + CRLF;
            }

            PRINT_DATA.groups.forEach((group) => {
                out += boldOn + padR(String(group.customer).toUpperCase(), COLS) + CRLF + boldOff;
                out += group.contact + CRLF;
                if (group.branch) out += group.branch + CRLF;
                if (group.address) wrap(group.address, COLS).forEach((r) => (out += r + CRLF));

                out += '-'.repeat(COLS) + CRLF;

                group.lines.forEach((line) => {
                    const qty = nf(line.qty) + (line.unit ? ' ' + line.unit : '');
                    out += boldOn;
                    lineProductQty(line.product, qty).forEach((r) => (out += r + CRLF));
                    out += boldOff;

                    if (line.note) {
                        wrap('- ' + line.note, COLS - 2).forEach((r) => (out += '  ' + r + CRLF));
                    }
                });

                out += '='.repeat(COLS) + CRLF;
            });

            out += sizeTall + boldOn;
            out += padR('TOTAL QTY', COLS / 2) + padL(nf(PRINT_DATA.total_qty), COLS / 2 - 1) + CRLF;
            out += boldOff + sizeNormal;

            out += alignCenter + '--- ' + PRINT_DATA.machine + ' ---' + CRLF;
            out += CRLF + CRLF + CRLF;
            out += cut;

            return out;
        }

        // ===== QZ TRAY =====
        if (window.qz) {
            if (window.crypto && crypto.subtle) {
                qz.api.setSha256Type((d) => crypto.subtle.digest('SHA-256', new TextEncoder().encode(d)));
            } else {
                qz.api.setSha256Type((d) => d);
            }
            qz.api.setPromiseType((fn) => new Promise(fn));
        }

        async function connectQZ() {
            if (!window.qz) throw new Error('QZ Tray belum dimuat.');
            if (!qz.websocket.isActive()) await qz.websocket.connect();
        }

        // Tebak printer thermal dari daftar printer yang terpasang
        function guessPrinter(printers) {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (saved && printers.includes(saved)) return saved;

            const match = printers.find((p) => /pos\s*-?80|oiware|thermal|receipt|struk/i.test(p));
            return match || printers[0] || null;
        }

        async function loadPrinters() {
            await connectQZ();
            const printers = await qz.printers.find();
            const chosen = guessPrinter(printers);

            printerSelect.innerHTML = '';
            printers.forEach((p) => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                if (p === chosen) opt.selected = true;
                printerSelect.appendChild(opt);
            });

            return chosen;
        }

        printerSelect.addEventListener('change', function() {
            localStorage.setItem(STORAGE_KEY, this.value);
            setStatus('Printer disimpan: ' + this.value);
        });

        async function directPrint(silent = false) {
            try {
                setStatus('Menghubungkan ke QZ Tray…');

                const chosen = printerSelect.value || (await loadPrinters());

                if (!chosen) throw new Error('Tidak ada printer terdeteksi.');

                localStorage.setItem(STORAGE_KEY, chosen);

                const config = qz.configs.create(chosen, {
                    encoding: 'CP437',
                    altPrinting: true,
                });

                await qz.print(config, [{
                    type: 'raw',
                    format: 'command',
                    data: buildEscPos(),
                }]);

                setStatus('Terkirim ke ' + chosen + ' ✅');
            } catch (e) {
                setStatus('Gagal cetak langsung: ' + e.message);

                if (!silent) {
                    alert(
                        'Gagal cetak langsung.\n' +
                        'Pastikan aplikasi QZ Tray sedang berjalan dan printer terpasang.\n\n' +
                        e.message
                    );
                }

                return false;
            }

            return true;
        }

        document.getElementById('btnDirectPrint').addEventListener('click', () => directPrint(false));

        // 🔧 Begitu halaman dibuka → langsung cetak tanpa dialog.
        //    Kalau QZ Tray tidak aktif, baru jatuh ke dialog print browser.
        window.addEventListener('load', async function() {
            try {
                await loadPrinters();
            } catch (e) {
                setStatus('QZ Tray tidak aktif — memakai dialog print browser.');
                window.print();
                return;
            }

            const ok = await directPrint(true);

            if (!ok) {
                window.print();
            }
        });
    </script>
</body>

</html>
