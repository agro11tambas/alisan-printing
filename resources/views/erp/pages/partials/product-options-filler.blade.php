{{--
    Pengisi opsi produk dari <template>.

    Masalah yang diselesaikan: form yang punya banyak baris item biasanya
    me-render seluruh katalog produk sebagai <option> di DALAM loop barisnya,
    jadi ukuran HTML-nya = jumlah produk x jumlah baris. Katalog 1.500 produk
    dengan 30 baris item berarti 45.000 <option> — halaman jadi berat di server
    maupun di browser.

    Pola yang dipakai sebagai gantinya:
      1. Katalog dirender SEKALI saja, di dalam sebuah <template>.
      2. Tiap baris item cuma membawa <option> yang sedang terpilih, supaya
         nilainya tetap terbaca sebelum JS jalan (dan tetap ikut ter-submit
         seandainya JS gagal).
      3. Partial ini menyalin daftar opsi dari <template> ke tiap select.

    Parameter:
      $templateId        id elemen <template> yang memuat katalog (harus berisi
                         satu select ber-class .select-product)
      $containerSelector wadah baris item yang select-nya mau diisi

    Setelah partial ini, tersedia dua fungsi global:
      fillProductOptions(selectElement)
      fillAllProductOptions(rootElement)

    Partial ini sengaja TIDAK jalan sendiri. Panggil fillAllProductOptions() di
    awal blok ready halaman, sebelum select2 diinisialisasi — kalau select2
    jalan duluan, daftar opsinya sudah terlanjur dibaca dan salinan katalognya
    tidak ikut terpakai.
--}}
@php
    $templateId = $templateId ?? 'product_item_template';
    $containerSelector = $containerSelector ?? '#product_list';
@endphp

<script>
    (function () {
        var templateId = @json($templateId);
        var containerSelector = @json($containerSelector);

        var optionsHtml = (function () {
            var template = document.getElementById(templateId);
            if (!template) return '';

            // <template>.innerHTML aman dibaca walau isinya belum di-parse
            // sebagai DOM aktif.
            var holder = document.createElement('div');
            holder.innerHTML = template.innerHTML;

            var source = holder.querySelector('.select-product');
            return source ? source.innerHTML : '';
        })();

        window.fillProductOptions = function (select) {
            if (!select || !optionsHtml) return;

            var selected = select.value;
            select.innerHTML = optionsHtml;

            // Halaman retur memakai harga baris, bukan harga katalog: semua opsi
            // di baris itu membawa data-price yang sama. Kalau select-nya
            // menandai data-row-price, tempelkan ke seluruh opsi supaya
            // perilakunya sama persis dengan versi yang di-render server.
            var rowPrice = select.getAttribute('data-row-price');

            if (rowPrice !== null) {
                select.querySelectorAll('option[value]:not([value=""])').forEach(function (option) {
                    option.setAttribute('data-price', rowPrice);
                });
            }

            // Kembalikan pilihan sebelumnya. Kalau produknya sudah tidak ada di
            // katalog (mis. sudah dihapus), biarkan placeholder yang terpilih.
            if (selected) select.value = selected;
        };

        window.fillAllProductOptions = function (root) {
            var scope = root || document.querySelector(containerSelector) || document;

            scope.querySelectorAll('.select-product').forEach(window.fillProductOptions);
        };
    })();
</script>
