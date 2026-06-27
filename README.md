# Backend RT Admin — Laravel

REST API untuk aplikasi administrasi RT, dibangun dengan Laravel 11.

> Repository frontend tersedia di: https://github.com/achmadfikrihdytllh/Frontend-RT-Admin

---

## Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel 11 |
| Bahasa | PHP 8.2+ |
| Database | MySQL 8.0+ |
| API | RESTful JSON |

---

## Prasyarat

Pastikan perangkat kamu sudah terinstal:

- PHP >= 8.2
- Composer >= 2.x
- MySQL >= 8.0
- Git

---

## Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/achmadfikrihdytllh/Backend-RT-Admin.git
cd Backend-RT-Admin
```

### 2. Install Dependency PHP

```bash
composer install
```

### 3. Salin File Environment

```bash
cp .env.example .env
```

### 4. Konfigurasi Database

Buka file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rt_admin
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `rt_admin` di MySQL terlebih dahulu:

```sql
CREATE DATABASE rt_admin;
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Jalankan Migrasi Database

```bash
php artisan migrate
```

### 7. Jalankan Seeder (Data Awal)

```bash
php artisan db:seed
```

> Seeder akan mengisi data awal kategori iuran: **Satpam (Rp 100.000)** dan **Kebersihan (Rp 15.000)**.

### 8. Buat Symbolic Link Storage

```bash
php artisan storage:link
```

> Diperlukan agar foto KTP penghuni bisa diakses via URL publik.

### 9. Konfigurasi CORS

Buka file `config/cors.php` dan pastikan origin frontend diizinkan:

```php
'allowed_origins' => ['http://localhost:5173'],
```

### 10. Jalankan Server

```bash
php artisan serve
```

Backend akan berjalan di: `http://127.0.0.1:8000`

---

## Verifikasi Instalasi

Buka browser atau gunakan tool seperti curl untuk mengakses:

```
http://127.0.0.1:8000/api/ping
```

Response yang diharapkan:

```json
{
  "success": true,
  "message": "API Backend RT Admin berjalan dengan lancar!"
}
```

---

## Daftar Endpoint API

### Penghuni
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/residents` | Daftar semua penghuni |
| POST | `/api/residents` | Tambah penghuni baru |
| GET | `/api/residents/{id}` | Detail penghuni |
| PUT | `/api/residents/{id}` | Update penghuni |
| DELETE | `/api/residents/{id}` | Hapus penghuni |

### Rumah
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/houses` | Daftar semua rumah |
| POST | `/api/houses` | Tambah rumah baru |
| GET | `/api/houses/{id}` | Detail rumah beserta history |
| PUT | `/api/houses/{id}` | Update kode rumah |
| DELETE | `/api/houses/{id}` | Hapus rumah |
| POST | `/api/houses/{id}/assign` | Assign penghuni ke rumah |
| POST | `/api/houses/{id}/unassign` | Keluarkan penghuni dari rumah |

### Pembayaran
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/payments` | Daftar semua tagihan |
| POST | `/api/payments` | Catat pembayaran manual |
| GET | `/api/payments/{id}` | Detail tagihan |
| PATCH | `/api/payments/{id}/pay` | Lunasi tagihan |
| PUT | `/api/payments/{id}` | Update tagihan |
| DELETE | `/api/payments/{id}` | Hapus tagihan |
| POST | `/api/payments/generate-monthly` | Generate tagihan bulanan otomatis |
| GET | `/api/payments/outstanding` | Daftar tagihan belum lunas |

### Pengeluaran
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/expenses` | Daftar semua pengeluaran |
| POST | `/api/expenses` | Tambah pengeluaran |
| GET | `/api/expenses/{id}` | Detail pengeluaran |
| PUT | `/api/expenses/{id}` | Update pengeluaran |
| DELETE | `/api/expenses/{id}` | Hapus pengeluaran |

### Kategori Iuran
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/fee-categories` | Daftar kategori iuran |
| POST | `/api/fee-categories` | Tambah kategori iuran |

### Laporan
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/reports/dashboard` | Summary dashboard bulanan |
| GET | `/api/reports/summary` | Grafik pemasukan & pengeluaran tahunan |
| GET | `/api/reports/detail` | Detail laporan per bulan |
| GET | `/api/reports/outstanding-summary` | Ringkasan tunggakan |
| GET | `/api/reports/dashboard/export` | Export dashboard ke CSV |
| GET | `/api/reports/detail/export` | Export detail laporan ke CSV |
| GET | `/api/reports/outstanding-summary/export` | Export tunggakan ke CSV |

---

## Troubleshooting

### Error saat `composer install`
Pastikan versi PHP sudah >= 8.2:
```bash
php -v
```

### Error migrasi
Pastikan database sudah dibuat dan konfigurasi `.env` sudah benar. Untuk reset total:
```bash
php artisan migrate:fresh --seed
```
> **Perhatian:** Perintah ini menghapus semua data. Gunakan hanya saat setup awal.

### Foto KTP tidak bisa diakses
Jalankan ulang:
```bash
php artisan storage:link
```

### CORS Error dari frontend
Pastikan `config/cors.php` sudah menambahkan `http://localhost:5173` di `allowed_origins`, lalu restart server:
```bash
php artisan serve
```

### Port 8000 sudah digunakan
```bash
php artisan serve --port=8001
```
Lalu sesuaikan `baseURL` di `src/api.js` frontend.

---

## Lisensi

Project ini dibuat untuk keperluan administrasi RT. Hak cipta © 2026.