/**
 * Tombol "Share Invoice" (kelas .btn-share-invoice-image) di Sale List & Sale Orders.
 *
 * Alur: ambil HTML invoice -> render ke gambar (html2canvas) -> tawarkan tombol
 * "Kirim" -> navigator.share() dipanggil dari klik tombol itu.
 *
 * Kenapa dua tahap? navigator.share() hanya boleh dipanggil dalam beberapa detik
 * setelah gesture user. Versi lama memanggilnya setelah fetch + html2canvas
 * selesai, jadi di perangkat/koneksi yang lebih lambat gesture-nya sudah
 * kedaluwarsa dan share ditolak diam-diam (promise tidak di-catch): tombol
 * seolah mati. Sekarang gambar disiapkan dulu, lalu share dipicu dari klik baru.
 *
 * Kalau browser tidak bisa share file (Firefox, bukan HTTPS, dsb.) gambar
 * di-download, dan setiap kegagalan selalu ditampilkan ke user.
 */
(function () {
    'use strict';

    var sedangProses = false;

    function tampilkanError(judul, pesan) {
        if (window.Swal) {
            Swal.fire({ icon: 'error', title: judul, text: pesan });
        } else {
            alert(judul + '\n' + pesan);
        }
    }

    function tutupLoading() {
        if (window.Swal && Swal.isVisible()) Swal.close();
    }

    function tampilkanLoading(teks) {
        if (!window.Swal) return;
        // Popup dibuat sinkron oleh Swal.fire, jadi showLoading bisa langsung
        // dipanggil tanpa hook (didOpen baru ada di v10, proyek ini masih v9).
        Swal.fire({
            title: teks,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
        Swal.showLoading();
    }

    function downloadGambar(dataUrl, namaFile) {
        var link = document.createElement('a');
        link.href = dataUrl;
        link.download = namaFile;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function canvasKeBlob(canvas) {
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (blob) {
                if (blob) resolve(blob);
                else reject(new Error('Gagal mengubah invoice menjadi gambar.'));
            }, 'image/png');
        });
    }

    async function siapkanGambar(url) {
        if (typeof html2canvas !== 'function') {
            throw new Error('html2canvas belum termuat di halaman ini. Coba muat ulang halaman.');
        }

        var response = await fetch(url, { credentials: 'same-origin' });
        if (!response.ok) {
            throw new Error('Gagal mengambil invoice (HTTP ' + response.status + ').');
        }
        // Sesi habis -> Laravel redirect ke halaman login, bukan ke invoice.
        if (response.redirected && /login/i.test(response.url)) {
            throw new Error('Sesi login sudah habis. Silakan login ulang.');
        }

        var html = await response.text();

        var temp = document.createElement('div');
        temp.style.position = 'fixed';
        temp.style.left = '-99999px';
        temp.style.top = '0';
        // Lebar tetap supaya hasil gambar sama di HP maupun desktop.
        temp.style.width = '900px';
        temp.innerHTML = html;
        document.body.appendChild(temp);

        try {
            var invoiceContent = temp.querySelector('#invoiceContent');
            if (!invoiceContent) {
                throw new Error('Konten invoice tidak ditemukan. Kemungkinan sesi login sudah habis.');
            }

            var canvas = await html2canvas(invoiceContent, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false
            });

            var blob = await canvasKeBlob(canvas);
            return { blob: blob, dataUrl: canvas.toDataURL('image/png') };
        } finally {
            if (temp.parentNode) temp.parentNode.removeChild(temp);
        }
    }

    function bisaShareFile(file) {
        try {
            return !!(window.isSecureContext && navigator.share && navigator.canShare && navigator.canShare({ files: [file] }));
        } catch (e) {
            return false;
        }
    }

    async function kirimViaShare(file, teks, dataUrl, namaFile) {
        try {
            await navigator.share({ files: [file], title: 'Invoice', text: teks });
            // Tutup eksplisit: Swal v9 tidak selalu menutup dialog sendiri
            // setelah preConfirm selesai.
            if (window.Swal) Swal.close();
        } catch (e) {
            // User menutup dialog share sendiri: dialog dibiarkan terbuka
            // supaya bisa coba lagi atau Batal.
            if (e && e.name === 'AbortError') return;
            console.error('navigator.share gagal', e);
            downloadGambar(dataUrl, namaFile);
            if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Share tidak tersedia',
                    text: 'Gambar invoice sudah di-download. Kirim file tersebut secara manual.'
                });
            }
        }
    }

    document.addEventListener('click', async function (event) {
        var btn = event.target.closest('.btn-share-invoice-image');
        if (!btn) return;

        event.preventDefault();
        if (sedangProses) return;
        sedangProses = true;

        var url = btn.getAttribute('data-url');
        var nama = btn.getAttribute('data-customer') || '';
        var invoiceNo = btn.getAttribute('data-invoice') || btn.getAttribute('data-id') || 'invoice';
        var namaFile = 'invoice-' + String(invoiceNo).replace(/[^A-Za-z0-9_-]+/g, '_') + '.png';
        var teks = 'Halo ' + nama + ', berikut invoice pembelian Anda.';

        var hasil;
        try {
            tampilkanLoading('Menyiapkan gambar invoice...');
            hasil = await siapkanGambar(url);
        } catch (e) {
            console.error('Gagal menyiapkan gambar invoice.', e);
            tutupLoading();
            sedangProses = false;
            tampilkanError('Share invoice gagal', e && e.message ? e.message : 'Terjadi kesalahan.');
            return;
        }

        tutupLoading();
        sedangProses = false;

        var file = new File([hasil.blob], namaFile, { type: 'image/png' });

        if (!bisaShareFile(file)) {
            downloadGambar(hasil.dataUrl, namaFile);
            if (window.Swal) {
                Swal.fire({
                    icon: 'info',
                    title: 'Gambar invoice di-download',
                    text: 'Browser ini tidak mendukung share langsung. Kirim file yang sudah di-download secara manual.'
                });
            }
            return;
        }

        if (!window.Swal) {
            // Tanpa SweetAlert tidak ada tombol untuk gesture baru; coba langsung.
            await kirimViaShare(file, teks, hasil.dataUrl, namaFile);
            return;
        }

        // SweetAlert yang dibundel proyek (v9) belum punya tombol "deny", jadi
        // opsi download ditaruh sebagai tautan di dalam dialog.
        var dialog = Swal.fire({
            title: 'Invoice siap dikirim',
            imageUrl: hasil.dataUrl,
            imageAlt: 'Preview invoice',
            imageWidth: 320,
            html: '<a href="#" class="share-invoice-download">atau download gambarnya</a>',
            showCancelButton: true,
            confirmButtonText: 'Kirim',
            cancelButtonText: 'Batal',
            // Share dipanggil dari klik tombol ini supaya gesture-nya masih segar.
            preConfirm: function () {
                return kirimViaShare(file, teks, hasil.dataUrl, namaFile);
            }
        });

        var tautan = Swal.getPopup() && Swal.getPopup().querySelector('.share-invoice-download');
        if (tautan) {
            tautan.addEventListener('click', function (ev) {
                ev.preventDefault();
                downloadGambar(hasil.dataUrl, namaFile);
                Swal.close();
            });
        }

        await dialog;
    });
})();
