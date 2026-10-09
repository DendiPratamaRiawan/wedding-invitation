# Undangan Pernikahan Dendi & Amora

Undangan digital "Di Bawah Cahaya Purnama": HTML, CSS, dan JavaScript vanilla di frontend, PHP 8.1+ dan MySQL di backend. Tidak memakai framework.

## Menjalankan secara lokal (Laragon)

1. Salin `includes/config.sample.php` menjadi `includes/config.php`, lalu isi kredensial database.
2. Buat database `wedding_invitations` (utf8mb4).
3. Jalankan installer:
   - CLI: `php install/index.php <username> <password>`
   - atau buka `/install/` di browser. Installer hanya bisa dipakai selama belum ada akun admin.
4. Buka undangan di `http://localhost/wedding-invitations/` dan panel admin di `/admin/`.

Tidak ada tahap publikasi: setiap kali admin menekan **Simpan**, perubahan langsung tampil di undangan.

## Publikasi ke hosting

- Gunakan HTTPS. Isi `app_url` (mis. `https://domain-undangan.id`) dan `force_https => true` di `config.php`.
- Server harus Apache dengan `mod_rewrite` dan `AllowOverride All` (untuk tautan `/i/KODE`), PHP dengan ekstensi `pdo_mysql`, `gd` (WebP), `mbstring`, dan `fileinfo`.
- **Hapus folder `install/`** setelah instalasi.
- Sebaiknya ganti nama folder `admin/` dengan nama yang hanya diketahui pengelola. Semua tautan di dalamnya relatif, jadi tidak ada yang perlu diubah. Keamanan tetap dijaga oleh login di server, bukan oleh nama folder.
- Pastikan folder `uploads/` bisa ditulis oleh PHP.
- Backup database secara berkala (mis. ekspor lewat phpMyAdmin).

## Struktur

```
index.php                 Halaman undangan (render server, aman dari XSS)
css/style.css, responsive.css, admin.css
js/main.js                Sampul, animasi muncul, musik, salin
js/countdown.js           Hitung mundur
js/invitation.js          Kalender, galeri, RSVP, ucapan
js/admin*.js              Panel admin (tidak dimuat di halaman publik)
admin/                    Dashboard, tamu, RSVP & ucapan, pengaturan (cek sesi di server)
api/                      auth, wedding, guests, rsvp, wishes, dashboard
includes/                 Bootstrap, konfigurasi, helper (akses web diblokir)
assets/vendor/xlsx.full.min.js   SheetJS 0.20.3 untuk impor .xlsx/.xls (dimuat hanya saat impor)
uploads/                  Foto (dikompresi ke WebP) & audio. Eksekusi skrip diblokir
install/                  Skema SQL dan installer
```

## Keamanan yang diterapkan

- Tidak ada tautan atau tombol admin di halaman publik.
- Semua halaman admin dan endpoint API admin memeriksa sesi di server. Permintaan yang mengubah data wajib menyertakan token CSRF.
- Password di-hash (`password_hash`). Login dibatasi 5 kali gagal per 15 menit. RSVP dan ucapan juga dibatasi jumlahnya.
- Cookie sesi bersifat HttpOnly dan SameSite=Strict, dan menjadi Secure saat HTTPS. Halaman memakai Content-Security-Policy.
- Kode undangan berupa 8 karakter acak (`random_int`). Tamu hanya bisa melihat dan mengubah RSVP miliknya sendiri. Kode yang tidak dikenal atau sudah dinonaktifkan menampilkan sapaan umum.
- Ekspor CSV dilindungi dari *formula injection*.

## Sebelum dibagikan ke tamu

Panel admin menampilkan daftar data yang masih kosong (tanggal, tempat, alamat, dll.) sebagai pengingat — tidak memblokir. Periksa tanggal, alamat, teks keagamaan, dan nama keluarga (nama keluarga Amora di data awal masih *usulan sementara*) sebelum tautan dikirim.
