-- Update untuk menambahkan kolom tingkat ke tabel kelas
-- Jalankan script ini jika database sudah terinstall sebelumnya

-- Cek apakah kolom tingkat sudah ada
SET @dbname = DATABASE();
SET @tablename = "kelas";
SET @columnname = "tingkat";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_NAME = @tablename)
      AND (TABLE_SCHEMA = @dbname)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " INT NOT NULL DEFAULT 1 AFTER nama_kelas")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update data existing jika diperlukan
UPDATE kelas SET tingkat = 1 WHERE tingkat IS NULL OR tingkat = 0;

-- Tambahkan index untuk performa query
ALTER TABLE kelas ADD INDEX idx_tingkat (tingkat);