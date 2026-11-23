# Aplikasi Laundry

Aplikasi kasir dan administrasi laundry berbasis PHP dan MySQL. Dibuat tanpa framework dan tanpa Composer, seluruh aset (Bootstrap, ikon, template) sudah tersedia di dalam repo.

## Fitur

- Login dengan tiga tingkat akses: Administrator, Operator, Leader.
- Master data: customer, jenis service, level, dan user.
- Pembuatan order dengan beberapa layanan sekaligus.
- Pembayaran tunai beserta kembalian.
- Cetak struk.
- Pengaturan toko (nama, alamat, telepon, email) yang dipakai pada struk.
- Laporan transaksi per rentang tanggal: ringkasan order, pendapatan, piutang, total cucian, filter status, rekap per layanan, ekspor CSV, dan halaman cetak.
- Pencarian order berdasarkan kode order, nama customer, atau nomor telepon dari kolom pencarian di header.
- Dashboard ringkasan: order bulan ini, pendapatan, customer aktif, service aktif, order belum dibayar, dan lima order terbaru.

## Kebutuhan

| Komponen | Versi |
|---|---|
| PHP | 8.x (diuji pada 8.3.6) |
| MySQL | 8.x (diuji pada 8.0.30) atau MariaDB 10.4+ |
| Ekstensi PHP | `mysqli`, `mbstring`, `session` |
| Web server | Apache (Laragon/XAMPP) atau `php -S` |

Tidak perlu Composer atau Node.js.

## Struktur Folder

```
config/koneksi.php        Koneksi database
function.php              Fungsi auth, password, dan CSRF
index.php                 Halaman login
home.php                  Layout utama + router halaman
logout.php                Keluar dan hapus session
print.php                 Cetak struk
report-export.php         Ekspor laporan ke CSV
report-print.php          Halaman cetak laporan
content/                  Isi halaman (dashboard, order, report, master data, pengaturan)
inc/                      Header, sidebar, dan header keamanan
database/migrations/      Migrasi SQL manual
assets/                   CSS, JS, gambar, dan library
img/                      Logo aplikasi
laundry_1 (1).sql         Dump database terbaru
laundry_1.sql             Dump lama (jangan dipakai)
```

## Cara Pasang

### 1. Letakkan folder aplikasi

Salin folder ini ke direktori web server, misalnya `C:\laragon\www\web_laundry_aryo`.

### 2. Buat database

Buat database kosong bernama `laundry_1`:

```sql
CREATE DATABASE laundry_1 CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 3. Impor dump

Gunakan dump terbaru, bukan `laundry_1.sql`:

```
mysql -uroot -D laundry_1 < "laundry_1 (1).sql"
```

Atau lewat phpMyAdmin: pilih database `laundry_1` → Import → pilih `laundry_1 (1).sql`.

### 4. Jalankan migrasi

Migrasi wajib dijalankan setelah impor, dengan urutan berikut:

```
mysql -uroot -D laundry_1 < database/migrations/001_deleted_at.sql
mysql -uroot -D laundry_1 < database/migrations/002_order_detail_price.sql
mysql -uroot -D laundry_1 < database/migrations/003_login_attempt.sql
mysql -uroot -D laundry_1 < database/migrations/004_laundry_status.sql
```

| Migrasi | Isi |
|---|---|
| `001_deleted_at.sql` | Mengubah `deleted_at` menjadi NULL agar data lama tampil dan soft delete bekerja |
| `002_order_detail_price.sql` | Menambah kolom `price` (snapshot harga) dan memperbaiki subtotal lama |
| `003_login_attempt.sql` | Membuat tabel `login_attempt` untuk pembatas percobaan login |
| `004_laundry_status.sql` | Menambah kolom `laundry_status` untuk memisahkan status pembayaran dari tahap cucian |

Semua migrasi aman dijalankan berulang.

### 5. Atur koneksi

Sesuaikan `config/koneksi.php` bila user, password, atau nama database berbeda:

```php
$hostname     = "localhost";
$hostusername = "root";
$password     = "";
$data_base    = "laundry_1";
```

### 6. Jalankan aplikasi

Dengan Laragon/XAMPP, buka:

```
http://localhost/web_laundry_aryo/
```

Tanpa Apache, dari folder proyek:

```
php -S 127.0.0.1:8080
```

Lalu buka `http://127.0.0.1:8080/index.php`.

## Akun Bawaan

Akun berikut berasal dari dump dan hanya untuk pemakaian lokal:

| Email | Password | Level |
|---|---|---|
| `bruno@gmail.com` | `123` | Administrator |
| `onana48@gmail.com` | `1234` | Operator |
| `ferguson@gmail.com` | `12345` | Leader |

Password pada dump masih plaintext. Saat login pertama, aplikasi otomatis mengubahnya menjadi hash bcrypt. **Ganti semua password bawaan sebelum aplikasi dipakai di server publik**, misalnya lewat menu User atau:

```bash
php -r "echo password_hash('password-baru', PASSWORD_DEFAULT), PHP_EOL;"
```

Lalu simpan hasilnya ke kolom `password` milik user terkait.

## Hak Akses

| Halaman | Administrator (9) | Operator (10) | Leader (11) |
|---|---|---|---|
| Dashboard | ✔ | – | – |
| Customer, Service, Level, User | ✔ | – | – |
| Pengaturan toko | ✔ | – | – |
| Order dan tambah order | ✔ | ✔ | – |
| Report | ✔ | – | ✔ |
| Ekspor CSV dan cetak laporan | ✔ | – | ✔ |

Level lain atau halaman di luar hak akses akan dialihkan ke halaman awal sesuai level. Level yang tidak dikenal akan diakhiri session-nya.

Kolom pencarian order di header hanya tampil untuk Administrator dan Operator, karena hanya keduanya yang boleh membuka daftar order.

## Alur Pemakaian

1. Login di `index.php`.
2. Atur identitas toko di menu **Pengaturan** (nama, alamat, telepon, email). Data ini muncul di struk.
3. Tambahkan customer dan jenis service pada menu Master Data.
4. Buat order di menu Order → Tambah Order, pilih customer, layanan, dan qty.
5. Simpan order. Order baru selalu berstatus **Belum Bayar** dan **Antrian**.
6. Buka detail order, isi jumlah bayar, lalu simpan. Status pembayaran berubah menjadi **Lunas**; status cucian berjalan terpisah.
7. Majukan status cucian dari halaman detail: **Dicuci** → **Siap Diambil** → **Sudah Diambil**. Tahap terakhir mencatat serah terima di `trans_laundry_pickup`.
8. Cari order lewat kolom pencarian di header memakai kode order, nama customer, atau nomor telepon. Tekan **Tampilkan semua** untuk menghapus filter.
9. Pantau transaksi lewat menu Report: pilih rentang tanggal dan status, lihat ringkasan, lalu **Export CSV** atau **Cetak**.

Catatan perhitungan:

- Qty diisi dalam **kilogram** dan disimpan dalam **gram** di database.
- Harga layanan disalin ke `trans_order_detail.price` saat order dibuat, sehingga perubahan harga tidak mengubah order lama.
- Total order dihitung di server, bukan dari input browser.
- Status dibagi dua: pembayaran `0` (Belum Bayar) / `1` (Lunas), dan tahap cucian `0` Antrian, `1` Dicuci, `2` Siap Diambil, `3` Sudah Diambil.
- Status cucian hanya bisa maju satu tahap, dan **Sudah Diambil** baru bisa dipilih setelah order lunas.

## Aturan Hapus Data

| Data | Jenis hapus | Syarat |
|---|---|---|
| Customer | Soft delete (`deleted_at`) | – |
| Jenis service | Soft delete | – |
| Level | Soft delete | – |
| Order | Soft delete | Hanya order yang belum lunas |
| User | Hard delete | Tidak bisa menghapus akun sendiri |

Semua penghapusan memakai `POST`, dialog konfirmasi, dan token CSRF.

## Keamanan

- Password memakai `password_hash`/`password_verify` dengan fallback SHA1 dan plaintext untuk data lama, lalu otomatis di-rehash ke bcrypt.
- Token CSRF pada seluruh form yang mengubah data.
- Cookie session `HttpOnly`, `SameSite=Lax`, dan `session.use_strict_mode=1`.
- Session ID diperbarui setelah login berhasil.
- Login dibatasi 5 kali gagal per email + IP dalam 15 menit.
- Header keamanan: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, dan `Permissions-Policy`.
- Output pada halaman daftar dan form di-escape dengan `htmlspecialchars`.

Sebelum dipakai di produksi:

1. Jalankan aplikasi lewat HTTPS.
2. Tambahkan `'secure' => true` pada `session_set_cookie_params()` di `index.php`, `home.php`, `print.php`, dan `logout.php`.
3. Ganti kredensial database dan password akun bawaan.
4. Matikan `display_errors` dan arahkan log ke file.
5. Batasi akses folder `database/` dan file `.sql`.

## Pemeliharaan

Backup database:

```
mysqldump -uroot laundry_1 > backup_laundry.sql
```

Membersihkan catatan login lama secara manual:

```sql
DELETE FROM login_attempt WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY);
```

Aplikasi juga menjalankan pembersihan ini otomatis setiap ada percobaan login.

## Pemecahan Masalah

| Gejala | Penyebab | Solusi |
|---|---|---|
| Muncul tulisan `koneksi gagal` | MySQL mati atau data koneksi salah | Nyalakan MySQL, cek `config/koneksi.php` |
| Daftar data kosong setelah impor | `deleted_at` masih berisi `0000-00-00` | Jalankan `001_deleted_at.sql` |
| Subtotal order lama tidak sesuai | Subtotal lama hanya menyimpan harga | Jalankan `002_order_detail_price.sql` |
| Tidak bisa login, pesan "Terlalu banyak percobaan login" | Terblokir 5 percobaan gagal | Tunggu 15 menit atau kosongkan tabel `login_attempt` |
| Form ditolak dengan pesan "Sesi formulir tidak valid" | Token CSRF kedaluwarsa | Muat ulang halaman lalu kirim ulang |
| Halaman dialihkan terus ke halaman awal | Halaman tidak diizinkan untuk level tersebut | Sesuaikan `id_level` user |

## Keterbatasan

- Status cucian belum mendukung pembatalan atau mundur satu tahap.
- Pengaturan toko hanya menyimpan satu baris identitas; belum ada unggah logo.
- Pencarian hanya mencakup daftar order, belum customer atau service.
- Belum ada pagination pada daftar order dan customer.
- Laporan menampilkan seluruh baris pada periode terpilih tanpa pagination.
- Migrasi dijalankan manual, belum ada runner otomatis.

## Riwayat Versi

### v1.2.0 (dalam pengerjaan)
- Laporan memakai filter `GET`: rentang tanggal, status bayar, dan status cucian, sehingga tautan laporan bisa dibagikan.
- Ringkasan laporan: jumlah order, pendapatan lunas, piutang, dan total cucian.
- Rekap qty dan subtotal per jenis layanan.
- Ekspor CSV dengan BOM UTF-8, total, dan proteksi formula injection.
- Halaman cetak laporan A4 berisi identitas toko dan rekap layanan.

### v1.1.0
- Status pembayaran dipisah dari tahap cucian: Antrian, Dicuci, Siap Diambil, Sudah Diambil.
- Status cucian hanya bisa maju satu tahap; **Sudah Diambil** baru bisa dipilih setelah order lunas.
- Estimasi selesai tampil di daftar order, dashboard, dan struk, lengkap dengan penanda **Lewat**.
- Halaman **Pengaturan Toko** untuk mengubah identitas yang dipakai pada struk.
- Pembayaran tidak lagi otomatis menandai cucian sudah diambil; serah terima dicatat terpisah.
