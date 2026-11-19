-- 001_deleted_at.sql
-- Tujuan: menyelaraskan kolom `deleted_at` dengan query aplikasi yang memakai `IS NULL`.
-- Dump lama menyimpan '0000-00-00 00:00:00' (bukan NULL) dan kolomnya NOT NULL,
-- sehingga daftar data tampil kosong dan insert baru bisa gagal di mode strict.
--
-- Cara menjalankan:
--   mysql -uroot laundry_1 < database/migrations/001_deleted_at.sql
--
-- Urutan penting: ALTER dulu agar kolom boleh NULL, baru UPDATE.
-- Jika UPDATE dijalankan saat kolom masih NOT NULL, MySQL mode non-strict
-- akan menyimpan zero-date lagi (bukan NULL).
--
-- Aman dijalankan berulang: ALTER bersifat idempoten dan UPDATE hanya
-- mengubah nilai zero-date.

SET SESSION sql_mode = '';

ALTER TABLE customer MODIFY deleted_at DATETIME NULL DEFAULT NULL;
UPDATE customer SET deleted_at = NULL WHERE deleted_at = '0000-00-00 00:00:00';

ALTER TABLE level MODIFY deleted_at DATETIME NULL DEFAULT NULL;
UPDATE level SET deleted_at = NULL WHERE deleted_at = '0000-00-00 00:00:00';

ALTER TABLE type_of_service MODIFY deleted_at DATETIME NULL DEFAULT NULL;
UPDATE type_of_service SET deleted_at = NULL WHERE deleted_at = '0000-00-00 00:00:00';

ALTER TABLE trans_order MODIFY deleted_at DATETIME NULL DEFAULT NULL;
UPDATE trans_order SET deleted_at = NULL WHERE deleted_at = '0000-00-00 00:00:00';
