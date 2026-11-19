-- 002_order_detail_price.sql
-- Tujuan:
--   1. Menyimpan snapshot harga layanan saat order dibuat (kolom `price`),
--      supaya perubahan harga layanan tidak mengubah nilai order lama.
--   2. Memperbaiki subtotal lama yang tersimpan sebesar harga saja,
--      bukan harga x qty (bug di content/tambah-order.php).
--
-- Cara menjalankan:
--   mysql -uroot laundry_1 < database/migrations/002_order_detail_price.sql
--
-- Aman dijalankan berulang: penambahan kolom dicek lewat information_schema
-- dan UPDATE hanya menyentuh baris yang perlu diperbaiki.

SET SESSION sql_mode = '';

-- 1. Tambah kolom price jika belum ada
--    (MySQL 8 belum mendukung ALTER TABLE ... ADD COLUMN IF NOT EXISTS)
SET @has_price = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'trans_order_detail'
      AND column_name = 'price'
);

SET @sql = IF(
    @has_price = 0,
    'ALTER TABLE trans_order_detail ADD COLUMN price INT NOT NULL DEFAULT 0 AFTER qty',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Isi snapshot harga untuk data lama dari harga layanan saat ini
UPDATE trans_order_detail d
JOIN type_of_service s ON s.id = d.id_service
SET d.price = s.price
WHERE d.price = 0;

-- 3. Hitung ulang subtotal: harga x (qty gram / 1000)
UPDATE trans_order_detail
SET subtotal = price * (qty / 1000)
WHERE qty > 0;
