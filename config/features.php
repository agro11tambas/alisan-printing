<?php

return [

    /*
     * Modul "Design Customer" (galeri design milik customer + tombol pilih
     * design di halaman Design). Dimatikan dulu atas permintaan, tapi kode,
     * tabel, dan route-nya tetap ada supaya bisa dinyalakan lagi tanpa deploy
     * ulang: set CUSTOMER_DESIGN_ENABLED=true di .env lalu
     * `php artisan config:clear`.
     */
    'customer_design' => filter_var(env('CUSTOMER_DESIGN_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

];
