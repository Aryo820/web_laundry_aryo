-- Tabel pembatas percobaan login (idempoten)
-- Dijalankan manual: mysql -uroot -D laundry_1 < database/migrations/003_login_attempt.sql
CREATE TABLE IF NOT EXISTS login_attempt (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(100) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_attempt_lookup (email, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
