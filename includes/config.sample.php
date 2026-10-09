<?php
// Salin file ini menjadi config.php lalu sesuaikan nilainya.
return [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'wedding_invitations',
    'db_user' => 'root',
    'db_pass' => '',

    // URL publik undangan tanpa garis miring di akhir, mis. https://domain-undangan.id
    // Kosongkan untuk deteksi otomatis (cukup untuk pengembangan lokal).
    'app_url' => '',

    // true di server produksi dengan HTTPS (cookie sesi hanya dikirim via HTTPS).
    'force_https' => false,
];
