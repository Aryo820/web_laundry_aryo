-- 004_laundry_status.sql
-- Tujuan: memisahkan status pembayaran dari tahap cucian.
--   order_status   : 0 belum bayar, 1 lunas
--   laundry_status : 0 antrian, 1 dicuci, 2 siap diambil, 3 sudah diambil
--
-- Cara menjalankan:
--   mysql -uroot -D laundry_1 < database/migrations/004_laundry_status.sql
--
-- Aman dijalankan berulang: kolom hanya ditambah bila belum ada, dan backfill
-- hanya jalan saat kolom baru dibuat supaya status yang sudah diubah kasir
-- tidak tertimpa.

SET @ada_kolom := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trans_order'
      AND COLUMN_NAME = 'laundry_status'
);

SET @sql := IF(
    @ada_kolom = 0,
    'ALTER TABLE trans_order ADD COLUMN laundry_status TINYINT NOT NULL DEFAULT 0 AFTER order_status',
    'SET @noop = 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Order lama yang sudah lunas dianggap sudah diserahkan ke customer.
SET @sql := IF(
    @ada_kolom = 0,
    'UPDATE trans_order SET laundry_status = 3 WHERE order_status = 1 AND deleted_at IS NULL',
    'SET @noop = 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
