-- 007_receipt_setting.sql
-- Tujuan: pengaturan tampilan struk.
--   receipt_logo   : path logo relatif dari root proyek, NULL kalau tidak ada
--   receipt_footer : teks footer struk, NULL berarti pakai teks bawaan
--   receipt_paper  : lebar kertas struk, 58 atau 80 (mm)
--
-- Cara menjalankan:
--   mysql -uroot -D laundry_1 < database/migrations/007_receipt_setting.sql
--
-- Aman dijalankan berulang: setiap kolom hanya ditambah bila belum ada.

SET @ada_logo := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'setting'
      AND COLUMN_NAME = 'receipt_logo'
);
SET @sql := IF(
    @ada_logo = 0,
    'ALTER TABLE setting ADD COLUMN receipt_logo VARCHAR(255) NULL AFTER phone',
    'SET @noop = 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ada_footer := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'setting'
      AND COLUMN_NAME = 'receipt_footer'
);
SET @sql := IF(
    @ada_footer = 0,
    'ALTER TABLE setting ADD COLUMN receipt_footer VARCHAR(100) NULL AFTER receipt_logo',
    'SET @noop = 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ada_paper := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'setting'
      AND COLUMN_NAME = 'receipt_paper'
);
SET @sql := IF(
    @ada_paper = 0,
    'ALTER TABLE setting ADD COLUMN receipt_paper TINYINT NOT NULL DEFAULT 80 AFTER receipt_footer',
    'SET @noop = 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
