<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

# Private Summarizer AI

Aplikasi web berbasis Laravel untuk meringkas teks dan PDF menggunakan model AI yang berjalan secara lokal dengan LM Studio. Aplikasi ini juga dilengkapi dengan fitur untuk membuat dan berlatih menggunakan flashcard.

Prasyarat (Perangkat Lunak yang Dibutuhkan)
Pastikan perangkat lunak berikut sudah terpasang di komputer Anda sebelum memulai:
- XAMPP: Untuk menyediakan lingkungan server web (Apache, MySQL, PHP). Alternatif seperti WAMP, MAMP, atau Laragon juga bisa digunakan.
- Composer: Manajer paket untuk PHP, digunakan untuk menginstal dependensi Laravel.
- Git: Sistem kontrol versi untuk mengunduh (clone) repositori.
- LM Studio: Aplikasi untuk mengunduh dan menjalankan model AI secara lokal.

# Langkah-langkah Instalasi
Ikuti langkah-langkah ini secara berurutan untuk menjalankan proyek di komputer lokal Anda.

1. Clone Repositori
Buka terminal (Command Prompt, PowerShell, atau Git Bash) di direktori tempat Anda biasa menyimpan proyek (misalnya, C:\xampp\htdocs), lalu jalankan perintah berikut:

```
git clone https://github.com/tehijoo/summary-ai.git
```

Setelah selesai, masuk ke dalam folder proyek yang baru dibuat:
```
cd summary-ai
```

2. Instal Dependensi PHP
Jalankan Composer untuk mengunduh semua paket PHP yang dibutuhkan oleh Laravel.

```
composer install
```

3. Siapkan File Konfigurasi (.env)
Salin file konfigurasi contoh menjadi file konfigurasi utama, lalu generate kunci aplikasi.

```
# Untuk pengguna Windows Command Prompt
copy .env.example .env

# Jalankan perintah ini setelah menyalin
php artisan key:generate
```

4. Siapkan Database
   - Buka XAMPP Control Panel dan pastikan Apache dan MySQL sudah berjalan (running).
   - Buka browser dan akses http://localhost/phpmyadmin.
   - Buat database baru dengan nama, misalnya, summary_ai.
   - Buka file .env yang tadi Anda buat di dalam proyek.
   - Sesuaikan baris berikut dengan konfigurasi database Anda. Untuk XAMPP standar, biasanya seperti ini:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=summary_ai
DB_USERNAME=root
DB_PASSWORD=
```
5. Jalankan Migrasi Database
Perintah ini akan membuat semua tabel yang dibutuhkan oleh aplikasi (seperti users, conversations, flashcard_sets, dll.) di dalam database summary_ai Anda.

```
php artisan migrate
```
6. Siapkan Model AI (LM Studio)
   - Buka aplikasi LM Studio.
   - Di tab pencarian (ikon kaca pembesar), cari model berikut: ```stabilityai/stablelm-2-zephyr-1_6b```
   - Unduh salah satu versi GGUF yang direkomendasikan (misalnya, ```Q4_K_M```).
   - Setelah unduhan selesai, pindah ke tab Server (ikon ```<->```).
   - Di bagian atas, pilih model ```StableLM``` yang sudah Anda unduh.
   - Klik tombol hijau "Start Server". Tunggu hingga log menunjukkan server berjalan di ```localhost:1234.```

# Menjalankan Aplikasi
Setiap kali Anda ingin menjalankan aplikasi ini, pastikan kedua server berikut sudah aktif:
1. Server AI:
   - Buka LM Studio, muat model, dan pastikan server API-nya sudah di-"Start".
2. Server Web Laravel:
   - Buka terminal di folder proyek Anda dan jalankan:

```
php artisan serve
```

3. Akses Aplikasi:
   - Buka browser Anda dan kunjungi alamat http://localhost:8000.
