-- Database: cbt_online
CREATE DATABASE IF NOT EXISTS cbt_online;
USE cbt_online;

-- Tabel: sekolah
CREATE TABLE IF NOT EXISTS sekolah (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_sekolah VARCHAR(255) NOT NULL,
    alamat TEXT,
    npsn VARCHAR(20),
    logo VARCHAR(255),
    tahun_ajaran VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel: users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('admin', 'operator', 'guru', 'pengawas', 'peserta') NOT NULL,
    foto VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel: kelas
CREATE TABLE IF NOT EXISTS kelas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kelas VARCHAR(50) NOT NULL,
    jurusan VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel: mata_pelajaran
CREATE TABLE IF NOT EXISTS mata_pelajaran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_mapel VARCHAR(20) UNIQUE NOT NULL,
    nama_mapel VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel: ruang_ujian
CREATE TABLE IF NOT EXISTS ruang_ujian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_ruang VARCHAR(20) UNIQUE NOT NULL,
    nama_ruang VARCHAR(100) NOT NULL,
    kapasitas INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel: peserta
CREATE TABLE IF NOT EXISTS peserta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nisn VARCHAR(20),
    kelas_id INT,
    jenis_kelamin ENUM('L', 'P'),
    tanggal_lahir DATE,
    alamat TEXT,
    no_hp VARCHAR(20),
    nama_wali VARCHAR(255),
    token_ujian VARCHAR(100),
    status_ujian ENUM('belum_mulai', 'sedang_ujian', 'selesai') DEFAULT 'belum_mulai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id)
);

-- Tabel: bank_soal
CREATE TABLE IF NOT EXISTS bank_soal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    mata_pelajaran_id INT NOT NULL,
    kelas_id INT,
    guru_id INT,
    jenis_soal ENUM('pilihan_ganda', 'esai') NOT NULL,
    pertanyaan TEXT NOT NULL,
    gambar VARCHAR(255),
    audio VARCHAR(255),
    video VARCHAR(255),
    pilihan_a TEXT,
    pilihan_b TEXT,
    pilihan_c TEXT,
    pilihan_d TEXT,
    pilihan_e TEXT,
    jawaban_benar VARCHAR(1),
    jawaban_esai TEXT,
    bobot_soal INT DEFAULT 1,
    tingkat_kesulitan ENUM('mudah', 'sedang', 'sulit') DEFAULT 'sedang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id),
    FOREIGN KEY (guru_id) REFERENCES users(id)
);

-- Tabel: paket_soal
CREATE TABLE IF NOT EXISTS paket_soal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_paket VARCHAR(20) UNIQUE NOT NULL,
    nama_paket VARCHAR(255) NOT NULL,
    mata_pelajaran_id INT NOT NULL,
    kelas_id INT,
    jumlah_soal INT NOT NULL,
    durasi_menit INT NOT NULL,
    tanggal_mulai DATETIME NOT NULL,
    tanggal_selesai DATETIME NOT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    acak_soal BOOLEAN DEFAULT TRUE,
    acak_jawaban BOOLEAN DEFAULT TRUE,
    tampilkan_nilai BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mata_pelajaran_id) REFERENCES mata_pelajaran(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id)
);

-- Tabel: ujian (master ujian)
CREATE TABLE IF NOT EXISTS ujian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paket_soal_id INT NOT NULL,
    nama VARCHAR(255) NOT NULL,
    tanggal_mulai DATETIME NOT NULL,
    tanggal_selesai DATETIME NOT NULL,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paket_soal_id) REFERENCES paket_soal(id)
);

-- Tabel: peserta_ujian (relasi many-to-many antara peserta dan ujian)
CREATE TABLE IF NOT EXISTS peserta_ujian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peserta_id INT NOT NULL,
    ujian_id INT NOT NULL,
    status ENUM('belum_mulai', 'sedang_ujian', 'selesai') DEFAULT 'belum_mulai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id),
    FOREIGN KEY (ujian_id) REFERENCES ujian(id),
    UNIQUE KEY unique_peserta_ujian (peserta_id, ujian_id)
);

-- Tabel: sesi_ujian (sesi ujian per peserta)
CREATE TABLE IF NOT EXISTS sesi_ujian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peserta_id INT NOT NULL,
    ujian_id INT NOT NULL,
    waktu_mulai DATETIME,
    waktu_selesai DATETIME,
    status ENUM('belum_mulai', 'sedang_ujian', 'selesai', 'force_submit') DEFAULT 'belum_mulai',
    nilai DECIMAL(5,2) DEFAULT 0,
    benar INT DEFAULT 0,
    salah INT DEFAULT 0,
    kosong INT DEFAULT 0,
    ip_address VARCHAR(50),
    user_agent TEXT,
    tab_switch_count INT DEFAULT 0,
    jumlah_pelanggaran INT DEFAULT 0,
    device_fingerprint VARCHAR(255),
    browser_fingerprint VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id),
    FOREIGN KEY (ujian_id) REFERENCES ujian(id)
);

-- Tabel: urutan_soal (untuk acak soal per sesi)
CREATE TABLE IF NOT EXISTS urutan_soal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sesi_ujian_id INT NOT NULL,
    soal_id INT NOT NULL,
    urutan INT NOT NULL,
    FOREIGN KEY (sesi_ujian_id) REFERENCES sesi_ujian(id),
    FOREIGN KEY (soal_id) REFERENCES bank_soal(id)
);

-- Tabel: jawaban_peserta (jawaban peserta per soal)
CREATE TABLE IF NOT EXISTS jawaban_peserta (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sesi_ujian_id INT NOT NULL,
    soal_id INT NOT NULL,
    jawaban TEXT,
    ragu_ragu BOOLEAN DEFAULT FALSE,
    is_benar BOOLEAN,
    bobot_soal INT DEFAULT 1,
    waktu_jawab TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesi_ujian_id) REFERENCES sesi_ujian(id),
    FOREIGN KEY (soal_id) REFERENCES bank_soal(id),
    UNIQUE KEY unique_jawaban (sesi_ujian_id, soal_id)
);

-- Tabel: jadwal_ujian (jadwal pelaksanaan ujian)
CREATE TABLE IF NOT EXISTS jadwal_ujian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ujian_id INT NOT NULL,
    ruang_ujian_id INT NOT NULL,
    tanggal_ujian DATE NOT NULL,
    waktu_mulai TIME NOT NULL,
    waktu_selesai TIME NOT NULL,
    pengawas_id INT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ujian_id) REFERENCES ujian(id),
    FOREIGN KEY (ruang_ujian_id) REFERENCES ruang_ujian(id),
    FOREIGN KEY (pengawas_id) REFERENCES users(id)
);

-- Tabel: activity_logs (log aktivitas sistem)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    activity VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabel: log_violation (log pelanggaran anti-cheat)
CREATE TABLE IF NOT EXISTS log_violation (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sesi_ujian_id INT NOT NULL,
    violation_type VARCHAR(100) NOT NULL,
    violation_count INT DEFAULT 1,
    detail_pelanggaran TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesi_ujian_id) REFERENCES sesi_ujian(id)
);

-- Tabel: screenshots (screenshot monitoring)
CREATE TABLE IF NOT EXISTS screenshots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sesi_ujian_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sesi_ujian_id) REFERENCES sesi_ujian(id)
);

-- Tabel: device_tracking (tracking device peserta)
CREATE TABLE IF NOT EXISTS device_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peserta_id INT NOT NULL,
    ip_address VARCHAR(50),
    user_agent TEXT,
    device_info JSON,
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (peserta_id) REFERENCES peserta(id)
);

-- Tabel: anti_cheat_logs (log pelanggaran lama - deprecated, gunakan log_violation)
CREATE TABLE IF NOT EXISTS anti_cheat_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ujian_sesi_id INT NOT NULL,
    jenis_pelanggaran VARCHAR(100) NOT NULL,
    detail_pelanggaran TEXT,
    waktu_pelanggaran TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ujian_sesi_id) REFERENCES ujian_sesi(id)
);

-- Insert data default
INSERT INTO sekolah (nama_sekolah, alamat, npsn, tahun_ajaran) VALUES
('SMK Negeri 1 Contoh', 'Jl. Contoh No. 123, Kota Contoh', '12345678', '2024/2025');

INSERT INTO users (username, password, nama_lengkap, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@sekolah.com', 'admin');

-- Create indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_peserta_nis ON peserta(nis);
CREATE INDEX idx_peserta_kelas ON peserta(kelas_id);
CREATE INDEX idx_bank_soal_mapel ON bank_soal(mata_pelajaran_id);
CREATE INDEX idx_bank_soal_kelas ON bank_soal(kelas_id);
CREATE INDEX idx_paket_soal_mapel ON paket_soal(mata_pelajaran_id);
CREATE INDEX idx_paket_soal_kelas ON paket_soal(kelas_id);
CREATE INDEX idx_ujian_paket ON ujian(paket_soal_id);
CREATE INDEX idx_peserta_ujian_peserta ON peserta_ujian(peserta_id);
CREATE INDEX idx_peserta_ujian_ujian ON peserta_ujian(ujian_id);
CREATE INDEX idx_sesi_ujian_peserta ON sesi_ujian(peserta_id);
CREATE INDEX idx_sesi_ujian_ujian ON sesi_ujian(ujian_id);
CREATE INDEX idx_sesi_ujian_status ON sesi_ujian(status);
CREATE INDEX idx_urutan_soal_sesi ON urutan_soal(sesi_ujian_id);
CREATE INDEX idx_urutan_soal_soal ON urutan_soal(soal_id);
CREATE INDEX idx_jawaban_peserta_sesi ON jawaban_peserta(sesi_ujian_id);
CREATE INDEX idx_jawaban_peserta_soal ON jawaban_peserta(soal_id);
CREATE INDEX idx_jadwal_ujian_ujian ON jadwal_ujian(ujian_id);
CREATE INDEX idx_jadwal_ujian_ruang ON jadwal_ujian(ruang_ujian_id);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_created ON activity_logs(created_at);
CREATE INDEX idx_log_violation_sesi ON log_violation(sesi_ujian_id);
CREATE INDEX idx_screenshots_sesi ON screenshots(sesi_ujian_id);
CREATE INDEX idx_device_tracking_peserta ON device_tracking(peserta_id);