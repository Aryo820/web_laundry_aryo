-- 006_payment_method.sql
-- Tujuan: menyimpan metode pembayaran order.
--   payment_method : 'tunai' | 'transfer' | 'qris', NULL kalau belum dibayar
--
-- Cara menjalankan:
--   mysql -uroot -D laundry_1 < database/migrations/006_payment_method.sql
--
-- Aman dijalankan berulang: kolom hanya ditambah bila belum ada, dan backfill
-- hanya mengisi order lunas yang metodenya masih kosong. Order belum bayar
-- dibiarkan NULL supaya tidak terlihat seolah sudah memilih metode.

SET @ada_kolom := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'trans_order'
      AND COLUMN_NAME = 'payment_method'
);

SET @sql := IF(
    @ada_kolom = 0,
    'ALTER TABLE trans_order ADD COLUMN payment_method VARCHAR(20) NULL AFTER laundry_status',
    'SET @noop = 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Order lama yang sudah lunas dianggap dibayar tunai karena dulu tidak ada
-- pilihan metode. Order yang belum bayar tetap NULL.
UPDATE trans_order
SET payment_method = 'tunai'
WHERE order_status = 1
  AND payment_method IS NULL;
