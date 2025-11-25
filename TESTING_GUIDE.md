# 🧪 Panduan Testing Aplikasi Ujian Online CBT

## 📋 Daftar Testing

### 1. Testing Instalasi
- [ ] Jalankan setup.php untuk instalasi database
- [ ] Verifikasi semua tabel terbuat dengan benar
- [ ] Cek koneksi database berhasil

### 2. Testing Login & Authentication
- [ ] Login dengan admin/password (default)
- [ ] Login dengan peserta (NIS/password)
- [ ] Test logout dan session management
- [ ] Test redirect berdasarkan role

### 3. Testing Dashboard Admin
- [ ] Lihat statistik real-time
- [ ] Cek status server monitoring
- [ ] Test quick actions menu
- [ ] Verifikasi responsive design

### 4. Testing Manajemen Data Master
- [ ] CRUD Data Sekolah
- [ ] CRUD Kelas
- [ ] CRUD Mata Pelajaran
- [ ] CRUD Ruang Ujian
- [ ] CRUD Users (admin, operator, guru, pengawas)
- [ ] CRUD Peserta

### 5. Testing Bank Soal
- [ ] Input soal pilihan ganda
- [ ] Input soal esai
- [ ] Upload gambar/audio/video untuk soal
- [ ] Import soal dari Excel
- [ ] Export soal ke Excel
- [ ] Preview tampilan ujian
- [ ] Validasi soal dan kunci jawaban

### 6. Testing Sistem Ujian Online
- [ ] Buat paket soal
- [ ] Atur jadwal ujian
- [ ] Test timer hitung mundur otomatis
- [ ] Test auto submit ketika waktu habis
- [ ] Test resume ujian jika koneksi terputus
- [ ] Test auto save jawaban setiap 30 detik
- [ ] Test acak soal dan jawaban
- [ ] Test single session login

### 7. Testing Fitur Anti-Cheat
- [ ] Test deteksi pindah tab
- [ ] Test deteksi minimize browser
- [ ] Test blok F12 / Ctrl+Shift+I
- [ ] Test blok right-click
- [ ] Test blok copy, paste, select
- [ ] Test deteksi Alt+Tab
- [ ] Test deteksi Alt+F4
- [ ] Test lock screen mode
- [ ] Test deteksi screen recorder
- [ ] Test deteksi virtual machine
- [ ] Test auto capture 30 detik sekali

### 8. Testing Monitoring Ujian
- [ ] Lihat peserta yang sedang ujian
- [ ] Lihat peserta yang sudah selesai
- [ ] Lihat peserta yang belum mulai
- [ ] Tampilkan IP address peserta
- [ ] Tampilkan device & browser peserta
- [ ] Test deteksi koneksi (stable/disconnected)
- [ ] Tampilkan sisa waktu per peserta
- [ ] Test jumlah pelanggaran anti-cheat
- [ ] Test force submit ujian peserta

### 9. Testing Analisis Nilai
- [ ] Lihat daftar nilai peserta
- [ ] Lihat rekap nilai per kelas & mapel
- [ ] Lihat grafik benar/salah per soal
- [ ] Test analisis tingkat kesukaran soal
- [ ] Test analisis daya pembeda soal
- [ ] Test export nilai ke Excel/PDF
- [ ] Test rekap nilai keseluruhan

### 10. Testing Fitur Cetak
- [ ] Cetak kartu peserta ujian
- [ ] Cetak daftar hadir peserta
- [ ] Cetak berita acara ujian
- [ ] Cetak daftar pengawas
- [ ] Cetak nilai ujian
- [ ] Cetak analisa ujian

## 🔧 Cara Testing

### Persiapan Testing
1. Jalankan setup.php untuk instalasi database
2. Login dengan admin/password
3. Setup data sekolah
4. Tambahkan minimal 2 kelas
5. Tambahkan minimal 2 mata pelajaran
6. Tambahkan minimal 5 peserta
7. Buat bank soal dengan minimal 10 soal
8. Buat paket ujian

### Testing Flow Peserta
1. Login sebagai peserta
2. Pilih ujian yang tersedia
3. Mulai ujian
4. Test semua fitur anti-cheat
5. Selesaikan ujian
6. Lihat hasil ujian

### Testing Flow Admin
1. Login sebagai admin
2. Monitor ujian yang sedang berlangsung
3. Lihat detail pelanggaran anti-cheat
4. Force submit peserta jika perlu
5. Lihat analisis hasil ujian
6. Export data ke Excel/PDF

## 🚨 Troubleshooting

### Masalah Umum
1. **Database connection error**: Cek config/database.php
2. **Upload file error**: Cek permission folder uploads/
3. **Session timeout**: Cek php.ini session settings
4. **Anti-cheat not working**: Cek browser compatibility

### Browser yang Didukung
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

### Resolusi Minimum
- Desktop: 1024x768
- Mobile: 360x640

## 📊 Checklist Final
- [ ] Semua fitur berjalan tanpa error
- [ ] Responsive design teruji di berbagai device
- [ ] Anti-cheat system berfungsi dengan baik
- [ ] Data tersimpan dengan benar
- [ ] Export/import berfungsi
- [ ] Security teruji (SQL injection, XSS, CSRF)
- [ ] Performance optimal (loading time < 3 detik)