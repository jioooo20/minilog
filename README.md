# MiniLog

MiniLog adalah aplikasi manajemen incident berbasis Laravel 12 dengan Inertia.js, Vue 3, dan Tailwind CSS. Aplikasi ini dipakai untuk alur pelaporan, verifikasi, investigasi, perbaikan, review, closing, master data, notifikasi, dan audit log dengan pembagian role seperti operator, engineer, dan supervisor.

## Tech Stack

- Laravel 12
- PHP 8.2+
- Inertia.js
- Vue 3
- Vite
- Tailwind CSS
- MySQL / database relasional lain yang didukung Laravel

## Fitur Utama

- Authentication dan role based access
- Incident management dan workflow incident
- Master data management
- Notification center
- Audit log
- Document output PDF

## Requirement

- PHP 8.2 atau lebih baru
- Composer
- Node.js 18+ atau versi yang kompatibel dengan Vite
- NPM
- Database seperti MySQL, MariaDB, atau PostgreSQL

## Instalasi

### 1. Clone repository

```bash
git clone https://github.com/jioooo20/minilog
cd minilog
```

### 2. Install dependency PHP

```bash
composer install
```

### 3. Install dependency JavaScript

```bash
npm install
```

### 4. Siapkan file environment

```bash
copy .env.example .env
```

Jika memakai PowerShell, gunakan:

```powershell
Copy-Item .env.example .env
```

Jika memakai terminal selain Command Prompt, salin file `.env.example` ke `.env` dengan cara yang sesuai.

### 5. Generate application key

```bash
php artisan key:generate
```

### 6. Atur database di file `.env`

Sesuaikan nilai berikut dengan konfigurasi lokal kamu:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=minilog
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Jalankan migrasi beserta seeding

```bash
php artisan migrate:fresh --seed
```

## Menjalankan Aplikasi

### Opsi 1. Jalankan manual

Jalankan tiga proses ini di terminal terpisah:

```bash
php artisan serve
```

```bash
npm run dev
```

### Opsi 2. Jalankan semua sekaligus

Project ini sudah menyiapkan script development:

```bash
composer run dev
```

Perintah ini akan menjalankan Laravel server, queue listener, dan Vite secara bersamaan.

## Build untuk Production

```bash
npm run build
```

## Struktur Singkat Project

- `app/` untuk controller, model, service, policy, dan middleware
- `database/` untuk migration, factory, dan seeder
- `resources/` untuk frontend Inertia/Vue, CSS, dan view
- `routes/` untuk definisi route web dan auth
- `public/` untuk entry point aplikasi
