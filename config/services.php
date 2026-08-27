<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
     * Upload gambar invoice dikirim ke server luar di tengah request user,
     * sehingga user ikut menunggu jawabannya. Timeout dibuat pendek dan bisa
     * diatur: kalau server gambar tersendat, lebih baik gagal cepat daripada
     * menahan halaman belasan detik.
     */
    'image_upload' => [
        'token' => env('IMAGE_UPLOAD_TOKEN'),
        'url' => env('IMAGE_UPLOAD_URL', 'https://image.alisanprinting.com/upload12552.php'),
        'timeout' => (int) env('IMAGE_UPLOAD_TIMEOUT', 15),
        'connect_timeout' => (int) env('IMAGE_UPLOAD_CONNECT_TIMEOUT', 5),
    ],

    /*
     * Website Next.js menyimpan halaman katalog di cache-nya sendiri. Tanpa
     * ping ke endpoint revalidate, perubahan produk/category di ERP baru
     * kelihatan setelah cache halaman itu kedaluwarsa.
     *
     * `url` dipisah dari FRONTEND_WEBSITE_URL karena alamat yang dipakai
     * customer bisa berbeda dari alamat server Next-nya (CDN/proxy di depan).
     */
    'website' => [
        'url' => env('WEBSITE_REVALIDATE_URL', env('FRONTEND_WEBSITE_URL')),
        'secret' => env('WEBSITE_REVALIDATE_SECRET'),
        'timeout' => (int) env('WEBSITE_REVALIDATE_TIMEOUT', 5),
        'connect_timeout' => (int) env('WEBSITE_REVALIDATE_CONNECT_TIMEOUT', 3),

        /*
         * Umur cache jawaban API katalog publik. Cache dikosongkan tiap admin
         * menyimpan produk/kategori, jadi TTL ini hanya jaring pengaman kalau
         * ada perubahan data yang tidak lewat ERP. Isi 0 untuk mematikannya.
         */
        'catalog_cache_ttl' => (int) env('WEBSITE_CATALOG_CACHE_TTL', 300),
        'catalog_cache_store' => env('WEBSITE_CATALOG_CACHE_STORE', 'file'),
        'catalog_cache_defer_rebuild' => (bool) env('WEBSITE_CATALOG_CACHE_DEFER_REBUILD', true),
        'catalog_cache_rebuild_lock' => (int) env('WEBSITE_CATALOG_CACHE_REBUILD_LOCK', 900),

        // Bolehkah request web membangun ulang katalog? Default TIDAK.
        //
        // Membangun katalog penuh memakan puluhan detik sampai menit. Kalau itu
        // terjadi di proses web, satu proses PHP terpakai penuh selama itu, dan
        // di shared hosting seluruh halaman ERP ikut mengantre di belakangnya.
        // Access log produksi 27 Agustus 2026 mencatat /api/v1/ecommerce/products
        // tertahan sampai berjam-jam, dengan sale-list 1.901 detik dan /erp/welcome
        // 150 detik menumpuk di belakangnya.
        //
        // Yang membangun katalog adalah cron (`catalog:warm`, tiap 5 menit).
        // Request web hanya menyajikan salinan yang sudah jadi — kalau tidak ada
        // yang segar, salinan lama dipakai. Salinan lama jauh lebih baik daripada
        // ERP berhenti untuk semua orang.
        'catalog_cache_web_rebuild' => (bool) env('WEBSITE_CATALOG_CACHE_WEB_REBUILD', false),
        // Umur salinan lama. Panjang dengan sengaja: kesegaran diatur flush()
        // dan penanda `fresh`, ini cuma jaring pengaman supaya tidak pernah
        // ada kondisi cache benar-benar kosong yang berujung 503 ke website.
        'catalog_cache_stale_ttl' => (int) env('WEBSITE_CATALOG_CACHE_STALE_TTL', 604800),
    ],

];
