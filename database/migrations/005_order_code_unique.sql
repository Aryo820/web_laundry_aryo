-- 005_order_code_unique.sql
-- Tujuan: memastikan tidak ada dua order dengan kode yang sama.
-- Kode dibuat dari id hasil insert (TR-ddmmyy-NNN), jadi seharusnya selalu
-- unik. Indeks ini menjaga kalau ada bug atau input manual yang bentrok.
--
-- Cara menjalankan:
--   mysql -uroot -D laundry_1 < database/migrations/005_order_code_unique.sql
--
-- Aman dijalankan berulang: indeks hanya dibuat kalau belum ada.
--
-- Kalau ALTER gagal karena ada kode duplikat, cari dulu datanya:
--   SELECT order_code, COUNT(*) FROM trans_order
--   GROUP BY order_code HAVING COUNT(*) > 1;
-- Perbaiki kode duplikat tersebut, lalu jalankan migrasi ini lagi.

SET @has_index = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'trans_order'
      AND index_name = 'uniq_trans_order_code'
);

SET @sql = IF(
    @has_index = 0,
    'ALTER TABLE trans_order ADD UNIQUE KEY uniq_trans_order_code (order_code)',
    'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
