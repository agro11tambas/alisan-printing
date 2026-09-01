{{--
    Mesin evaluasi diskon untuk form order ERP (create/edit sale order & sale list).

    Sebelumnya tiap form punya rantai `if (apply_on === 'Product') ... else if ...`
    sendiri, jadi tiap penambahan scope harus disalin ke empat berkas. Sekarang
    "Apply On" boleh lebih dari satu dan syaratnya digabung AND, jadi logikanya
    dikumpulkan di sini dan keempat form memanggil `DiscountEngine`.

    Form order cuma tahu tiga scope: Product, Category, dan Mode. Scope
    EcommerceCategory dilewati karena datanya memang tidak ada di halaman ini —
    tapi kalau SEMUA scope diskon di luar jangkauan (mis. khusus ecommerce),
    diskonnya tidak berlaku sama sekali di sini.

    Butuh variabel $modeDiscounts dari controller.
--}}
<script>
    window.DiscountEngine = (function() {
        const MODE_DISCOUNTS = @json($modeDiscounts ?? []);

        // Scope yang datanya tersedia di form order ERP.
        const SUPPORTED_SCOPES = ['Product', 'Category', 'Mode'];

        function toQty(value) {
            // Qty ditampilkan dengan pemisah ribuan titik.
            return parseFloat(String(value ?? '').replace(/\./g, '')) || 0;
        }

        function toPrice(value) {
            return parseFloat(value) || 0;
        }

        /**
         * Ringkasan satu baris order yang dibutuhkan untuk mencocokkan diskon.
         */
        function rowInfo(row) {
            const select = row.find('select[name="product[]"]');
            const option = select.find('option:selected');
            const price = toPrice(row.find('input.price_before_discount').val()) ||
                toPrice(option.data('price'));
            const qty = toQty(row.find('input[name="qty[]"]').val());

            return {
                productId: String(select.val() ?? ''),
                categoryIds: (option.data('categories') || []).map(c => String(c.id)),
                mode: row.find('select[name="mode[]"]').val() || null,
                qty: qty,
                amount: price * qty,
            };
        }

        function scopesOf(discount) {
            const raw = Array.isArray(discount.apply_on_list) && discount.apply_on_list.length ?
                discount.apply_on_list :
                String(discount.apply_on || '').split(',');

            return raw.map(scope => String(scope).trim()).filter(Boolean);
        }

        function idStrings(value) {
            return (value || []).map(id => String(id));
        }

        function categoryIdsOf(discount) {
            if (Array.isArray(discount.category_ids)) return idStrings(discount.category_ids);
            // Payload lama cuma membawa kategori yang jadi jalur masuknya.
            if (discount.category_id != null) return [String(discount.category_id)];
            return null;
        }

        /**
         * Apakah satu baris memenuhi semua scope yang diuji.
         *
         * Daftar target yang tidak ada di payload dianggap cocok — itu payload
         * lama yang sudah tersaring lewat jalur relasinya sendiri.
         */
        function matchesScopes(discount, scopes, info) {
            return scopes.every(scope => {
                if (scope === 'Product') {
                    if (!Array.isArray(discount.product_ids)) return true;
                    return idStrings(discount.product_ids).includes(info.productId);
                }

                if (scope === 'Category') {
                    const ids = categoryIdsOf(discount);
                    if (ids === null) return true;
                    return ids.some(id => info.categoryIds.includes(id));
                }

                if (scope === 'Mode') {
                    if (!Array.isArray(discount.price_mode_slugs)) return true;
                    return info.mode !== null && discount.price_mode_slugs.includes(info.mode);
                }

                return false;
            });
        }

        /**
         * Akumulasi qty & nominal dari semua baris yang jadi sasaran diskon ini.
         *
         * Dipakai sebagai dasar pengecekan minimum. Untuk scope Mode, hitungannya
         * dikunci ke mode baris yang sedang dihitung — sama seperti perilaku lama —
         * supaya mode yang berbeda tidak saling menumpang minimum.
         */
        function targetTotals(discount, scopes, info) {
            let qty = 0;
            let amount = 0;

            $('.product-item').each(function() {
                const other = rowInfo($(this));

                if (scopes.includes('Mode') && other.mode !== info.mode) return;
                if (!matchesScopes(discount, scopes, other)) return;

                qty += other.qty;
                amount += other.amount;
            });

            return {
                qty,
                amount
            };
        }

        function isEligible(discount, row) {
            const info = rowInfo(row);
            const scopes = scopesOf(discount).filter(scope => SUPPORTED_SCOPES.includes(scope));

            // Semua scope-nya di luar jangkauan form ini (mis. khusus ecommerce).
            if (scopes.length === 0) return false;

            if (!matchesScopes(discount, scopes, info)) return false;

            // Scope Product tetap dihitung per baris, seperti sebelumnya.
            const basis = scopes.includes('Product') ?
                {
                    qty: info.qty,
                    amount: info.amount
                } :
                targetTotals(discount, scopes, info);

            const minimum = parseFloat(discount.minimum_qty_or_amount) || 0;

            return discount.minimum_based_on === 'Quantity of Items' ?
                basis.qty >= minimum :
                basis.amount >= minimum;
        }

        /**
         * Semua diskon yang mungkin kena baris ini, dari tiga jalur payload:
         * relasi produk, relasi kategori, dan daftar diskon ber-scope Mode.
         *
         * Satu diskon bisa datang dari lebih dari satu jalur (mis. Category +
         * Mode), jadi hasilnya di-dedupe per id.
         */
        function discountsForRow(row) {
            const option = row.find('select[name="product[]"] option:selected');
            const mode = row.find('select[name="mode[]"]').val();
            const collected = [].concat(option.data('discounts') || []);

            (option.data('categories') || []).forEach(category => {
                if (category.discounts) {
                    collected.push(...category.discounts);
                }
            });

            if (mode) {
                MODE_DISCOUNTS.forEach(discount => {
                    if ((discount.price_mode_slugs || []).includes(mode)) {
                        collected.push(discount);
                    }
                });
            }

            const seen = new Set();

            return collected.filter(discount => {
                if (!discount || discount.id == null) return true;
                if (seen.has(discount.id)) return false;
                seen.add(discount.id);
                return true;
            });
        }

        /**
         * Harga satuan setelah diskon yang berlaku untuk baris ini.
         */
        function priceAfterDiscount(row, priceBeforeDiscount) {
            let finalPrice = priceBeforeDiscount;

            discountsForRow(row).forEach(discount => {
                if (!isEligible(discount, row)) return;

                finalPrice = discount.type === 'Percentage' ?
                    priceBeforeDiscount - (priceBeforeDiscount * (parseFloat(discount.amount) || 0) / 100) :
                    Math.max(0, priceBeforeDiscount - (parseFloat(discount.amount) || 0));
            });

            return finalPrice;
        }

        return {
            modeDiscounts: MODE_DISCOUNTS,
            rowInfo,
            isEligible,
            discountsForRow,
            priceAfterDiscount,
        };
    })();
</script>
