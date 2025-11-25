# Aplikasi Ujian Online CBT (Computer Based Test)

Aplikasi ujian online berbasis web yang lengkap dengan fitur anti-cheat, monitoring real-time, dan sistem analisis hasil ujian.

## Fitur Utama

### ✅ Fitur Peserta
- Login dengan username/password
- Dashboard peserta dengan ujian yang tersedia
- Timer otomatis untuk setiap ujian
- Resume ujian jika koneksi terputus
- Lihat hasil ujian setelah selesai

### ✅ Fitur Admin
- Dashboard dengan statistik lengkap
- Manajemen data sekolah, kelas, mata pelajaran
- Manajemen user (admin, operator, guru, pengawas, peserta)
- Bank soal dengan import/export Excel
- Monitoring ujian real-time
- Sistem analisis hasil ujian
- Fitur cetak (kartu peserta, daftar hadir, nilai)

### ✅ Fitur Anti-Cheat
- Deteksi pindah tab/minimize
- Blok F12, right-click, copy-paste
- Auto-submit jika terdeteksi kecurangan
- Single session login
- Tracking IP address dan device

### ✅ Fitur Keamanan
- Password terenkripsi dengan bcrypt
- Session management
- Activity logging
- Backup & restore database

## Persyaratan Sistem

- **Web Server**: Apache/Nginx
- **PHP**: Versi 7.4 atau lebih tinggi
- **Database**: MySQL 5.7+ atau MariaDB 10.2+
- **Browser**: Chrome, Firefox, Safari, Edge (versi terbaru)

## Instalasi

### 1. Clone atau Download
```bash
git clone [repository-url]
cd cbt-online
```

### 2. Setup Database
1. Buat database MySQL baru dengan nama `cbt_online`
2. Import file SQL: `config/database.sql`
3. Atau jalankan setup otomatis dengan mengakses: `http://localhost/cbt-online/setup.php`

### 3. Konfigurasi Database
Edit file `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cbt_online');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan dengan password MySQL Anda
```

### 4. Setup Folder Permissions
Pastikan folder berikut memiliki permission write:
- `uploads/`
- `uploads/backup/`
- `uploads/soal/`
- `uploads/peserta/`

### 5. Login Pertama Kali
- **Admin**: username: `admin`, password: `password`
- **Peserta**: Buat akun peserta melalui admin panel

## Struktur Folder

```
cbt-online/
├── admin/                    # Panel admin
│   ├── dashboard.php
│   ├── includes/
│   ├── sekolah/
│   ├── users/
│   ├── bank_soal/
│   ├── ujian/
│   ├── monitoring/
│   ├── hasil/
│   └── cetak/
├── peserta/                  # Panel peserta
│   ├── dashboard.php
│   ├── ujian.php
│   └── hasil.php
├── config/                   # Konfigurasi
│   ├── database.php
│   └── database.sql
├── includes/                 # Fungsi umum
│   └── functions.php
├── assets/                   # Assets
│   ├── css/
│   ├── js/
│   ├── img/
│   ├── audio/
│   └── video/
├── uploads/                  # Upload files
│   ├── backup/
│   ├── soal/
│   └── peserta/
├── index.php                 # Halaman utama
├── login.php                 # Halaman login
├── logout.php                # Logout
├── setup.php                 # Setup database
└── README.md                 # Dokumentasi
```

## Cara Penggunaan

### Untuk Admin
1. Login dengan akun admin
2. Setup data sekolah di menu "Data Sekolah"
3. Tambahkan kelas dan mata pelajaran
4. Tambahkan user (guru, operator, peserta)
5. Buat bank soal untuk setiap mata pelajaran
6. Buat paket soal dan jadwal ujian
7. Monitor ujian yang sedang berlangsung

### Untuk Peserta
1. Login dengan NIS dan password
2. Pilih ujian yang tersedia
3. Kerjakan soal sesuai waktu yang ditentukan
4. Submit jawaban setelah selesai
5. Lihat hasil ujian (jika diizinkan)

## Troubleshooting

### Database Connection Error
- Pastikan MySQL service berjalan
- Cek konfigurasi di `config/database.php`
- Pastikan database `cbt_online` sudah dibuat

### Permission Error
- Set permission folder `uploads/` ke 755 atau 777
- Pastikan PHP memiliki akses write ke folder tersebut

### Session Error
- Pastikan session.save_path di php.ini memiliki permission yang benar
- Cek apakah cookies di browser diaktifkan

## Keamanan

- Password default harus diganti setelah instalasi
- Gunakan HTTPS untuk produksi
- Update PHP dan MySQL ke versi terbaru
- Backup database secara berkala

## Support

Untuk bantuan atau pertanyaan, silakan buat issue di repository GitHub atau hubungi developer.

## Lisensi

Aplikasi ini dikembangkan untuk keperluan pendidikan. Bebas digunakan dan dimodifikasi sesuai kebutuhan.# aaaaaaaaaa
