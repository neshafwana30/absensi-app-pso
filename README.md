# Absensi App

## Project Overview
Absensi App merupakan aplikasi berbasis web untuk mengelola absensi karyawan secara digital dan terintegrasi. Sistem mendukung *role-based access* untuk Admin, Operator, dan User (Employee), serta menyediakan fitur pengelolaan kehadiran, *QR Code attendance*, monitoring absensi, dan pengajuan izin kehadiran. Aplikasi dibangun menggunakan Laravel dan di-deploy menggunakan Docker, GitHub Actions CI/CD, serta Azure App Service.

## Key Features (Stable)
* **CRUD Positions:** Manajemen data jabatan/posisi karyawan.
* **CRUD Users:** Pengelolaan akun untuk Admin, Operator, dan Users (Employees).
* **CRUD Holidays:** Pengaturan dan pendataan hari libur.
* **CRUD Attendances:** Mendukung berbagai metode kehadiran (menggunakan tombol *check-in* konvensional maupun pemindaian *QRCode*).
* **Interactive Datatables:** Tabel data dinamis menggunakan Powergrid Livewire.
* **Export Reporting:** Mendukung ekspor data laporan absensi ke format Excel dan CSV.

## Fitur Baru (Sedang Dikembangkan)
* **Otomatisasi Reset Cuti Tahunan & Dashboard Cuti:** Mengotomatisasi proses reset jatah cuti setiap tanggal 1 Januari menggunakan Laravel Task Scheduling dan Artisan Command khusus (`php artisan cuti:reset`), dilengkapi dengan *dashboard* pemantauan sisa cuti dan persentase penggunaannya.
* **Dashboard Utama Ringkasan Kehadiran (HRD/Admin):** *Dashboard* analitik yang menampilkan statistik kehadiran *real-time* (total karyawan aktif, persentase harian, jumlah alpa), *log* aktivitas absensi terbaru, serta visualisasi grafik tren absensi dan keterlambatan bulanan.
* **Force Reset Password on First Login:** Peningkatan keamanan akun melalui penambahan *Middleware Security* yang akan mendeteksi status *login* pengguna baru dan mewajibkan mereka mengganti *password* sebelum dapat mengakses menu aplikasi lainnya.
* **Integrasi API Hari Libur Nasional:** Fitur untuk melakukan sinkronisasi jadwal hari libur nasional secara otomatis dan *auto-fill* jadwal absensi.

## Tech Stack
**Backend / Core:**
* **Bahasa Pemrograman:** PHP (versi 8.x).  
* **Framework:** Laravel (versi 9.17), dilengkapi dengan pemanfaatan Eloquent ORM, Laravel Task Scheduling, dan Middleware kustom.
* **Authentication:** Laravel Sanctum (untuk keamanan API).

**Frontend:**
* **Engine & Interactivity:** Laravel Livewire (membuat UI dinamis tanpa perlu banyak menulis JavaScript murni).  
* **JavaScript Libraries:** Alpine.js, jQuery, Axios (untuk HTTP requests), dan Chart.js (untuk rendering grafik analitik).  
* **UI/Styling:** Bootstrap (termasuk untuk komponen Dashboard UI).

**Database:**
* **RDBMS:** PostgreSQL.

**Key Packages & Features:**
* **QR Code Handling:** Menggunakan `simplesoftwareio/simple-qrcode` dan `html5-qrcode`.  
* **Document Generation:** `barryvdh/laravel-dompdf` untuk mengekspor data absensi/laporan.  
* **Data Tables:** `power-components/livewire-powergrid` untuk menampilkan tabel data yang interaktif.  
* **Asset Bundler:** Laravel Mix (berbasis Webpack) untuk mengkompilasi file CSS dan JavaScript. 

## Installation Guide & Prerequisites
Sebelum menjalankan proyek ini di local environment, pastikan sistem kamu sudah memenuhi persyaratan berikut:
* **Docker & Docker Desktop:** Pastikan aplikasi Docker sudah terinstal dan dalam status *running* di sistem kamu.
* **Environment Configuration:** Siapkan file konfigurasi lokal. Salin file `.env.example` bawaan dan ubah namanya menjadi `.env`. Jika kamu ingin menjalankan *automated testing*, buat juga duplikat file dengan nama `.env.testing`.
* **Database Client (Opsional):** Kamu bisa menggunakan *tools* seperti DBeaver atau PGAdmin untuk memantau database secara langsung.

Ubah konfigurasi database pada file `.env` milikmu agar selaras dengan pengaturan *container* PostgreSQL:
```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=absensi_db
DB_USERNAME=dev_user
DB_PASSWORD=secret_password
```
## Backend Dependencies Setup (Composer)
Langkah ini wajib dilakukan agar dependensi inti PHP terunduh sebelum Docker dijalankan.
- Install library PHP (gunakan flag --ignore-platform-reqs agar lolos dari syarat ketat PHP 8.1 jika kamu menggunakan versi lebih tinggi):
```
composer install --ignore-platform-reqs
```
- Buat application key baru:
```
php artisan key:generate
```
- Lakukan pembaruan pada paket Carbon untuk menjinakkan error sebelum melakukan migrasi agar sistem stabil di versi PHP terbaru:
```
composer update nesbot/carbon --ignore-platform-reqs
```

- Jalankan migrasi sekaligus suntik data
```
php artisan migrate:fresh --seed
```

## Frontend Asset Compilation (NPM)
Kompilasi aset UI sengaja dilakukan di luar Docker untuk menjaga image tetap ringan dan menghindari dependency conflict versi Node.js.
- Bersihkan direktori lama untuk memastikan instalasi bersih:
```
rmdir /s /q node_modules
del package-lock.json
```
- Install dependensi frontend secara spesifik untuk menghindari konflik dengan Laravel Mix v6:
```
npm install laravel-mix@6.0.43 webpack@5.65.0 webpack-cli@4.9.1 postcss@8.4.5 --save-dev --legacy-peer-deps
```
- Kompilasi aset UI agar siap digunakan:
```
npm run dev
```

## Docker Setup
Proyek ini menggunakan multi-container architecture di mana Nginx dan PHP-FPM dijalankan secara simultan di bawah pengawasan Supervisor.

Penting: Kompilasi aset frontend (Node.js/NPM) sengaja dikecualikan dari proses build Docker Image ini agar container tetap ringan dan terhindar dari konflik versi. Proses kompilasi frontend (NPM) tersebut sepenuhnya didelegasikan kepada GitHub Actions di dalam tahapan CI/CD.

Untuk mem-build dan menjalankan aplikasi, jalankan perintah berikut secara berurutan di terminal VS Code kamu:  

1. Build dan Jalankan Container
```
docker compose up --build -d
```
2. Bersihkan Cache Konfigurasi Laravel
```
docker exec -it absensi_app_container php artisan config:clear
```
3. Migrasi Struktur Tabel & Seeding Data
```
docker exec -it absensi_app_container php artisan migrate --seed
```
4. Verifikasi Status Container
```
docker compose ps
```

Mengakses Aplikasi:
- Website: Setelah seluruh perintah di atas selesai dan server berjalan, buka browser dan akses aplikasi melalui http://localhost:8000.
- Menghentikan Container: Jika sudah selesai melakukan pengembangan, kamu bisa mematikan container dengan menjalankan docker compose down di terminal.

## CI/CD Workflow
Aplikasi ini menggunakan GitHub Actions untuk menjalankan otomatisasi pengujian (Continuous Integration) sebelum kode digabungkan ke cabang utama. Alur kerja otomatisasi ini dibagi menjadi dua mekanisme utama:
### 1. Automated Integration Testing (integration_testing.yml)
- Trigger: Alur kerja ini otomatis aktif setiap kali terdapat aktivitas git push maupun pull request ke branch main dan development.
- Setup Runner: Pengujian dijalankan di lingkungan virtual terisolasi Ubuntu dengan PHP 8.1, pdo_pgsql, serta server database PostgreSQL 15.
- Tahapan Eksekusi: Menarik kode sumber terbaru, menginstal dependensi (composer install), membuat application key untuk testing, menjalankan migrasi tabel dari awal, dan mengeksekusi skenario pengujian fungsional (php artisan test).
- Status Report: GitHub akan memberikan indikator centang hijau (Success) jika lulus uji. Jika gagal, indikator silang merah (Failed) muncul dan proses penggabungan kode ditangguhkan.

### 2. Frontend Build Testing & Artifact Management (ci-build-test.yml)
- Trigger: Berjalan otomatis setiap ada aktivitas push atau pull request ke branch development.
- Tahapan Eksekusi: Menyiapkan lingkungan Node.js di runner Linux, mengunduh dependensi frontend bersih (npm ci), melakukan kompilasi aset production (npm run prod), lalu mengompres dan mengunggah hasil kompilasi folder public/ ke dalam Artifacts Storage GitHub agar siap diambil untuk deployment.

## Branching Strategy
Untuk menjaga stabilitas kode dan kelancaran kolaborasi tim, pengembangan fitur dilakukan dengan strategi pencabangan berikut:
- Branch main: Cabang produksi utama yang menyimpan kode stabil, bersih, dan siap dirilis. Kode di branch ini tidak boleh diubah secara langsung.
- Branch development: Pusat sentralisasi pengerjaan fitur baru, eksperimen, maupun perbaikan bug.

## Ketentuan Pengunggahan Kode Tim:
1. Wajib Menggunakan Cabang Pengembangan: Setiap fitur harus diunggah (push) atau diajukan melalui pull request ke branch development sebelum digabungkan ke branch main.
2. Pemantauan Pasca-Push: Setelah push, pengembang wajib memantau jalannya pengujian otomatis di tab Actions pada repositori GitHub.
3. Restriksi Status Gagal: Jika indikator pengujian merah (failed), penggabungan kode otomatis ditangguhkan. Pengembang wajib memperbaiki dan menguji ulang di lokal hingga mendapatkan centang hijau.

## Environment Architecture
Arsitektur infrastruktur sistem dibagi menjadi tiga lingkungan terpisah:

### 1. Local Development Environment
- Kontainerisasi: Menggunakan Docker Desktop. Aplikasi backend berjalan menggunakan Linux Alpine yang ramping, diawasi oleh Supervisor untuk menjalankan Nginx (Port 80) dan PHP-FPM secara simultan.
- Local Database: Berjalan di container PostgreSQL terpisah (Host: db, Port: 5432, DB: absensi_db). Disediakan juga database khusus bernama absensi_db_testing untuk keperluan pengujian fungsional lokal.
- Akses Lokal: Dapat diakses pada http://localhost:8000.

### 2. CI Environment (GitHub Actions Runner)
- Menggunakan server virtual berbasis Linux Ubuntu.
- Berfungsi sebagai lingkungan pengujian jangka pendek (ephemeral) untuk memeriksa dependensi, kompilasi aset frontend, dan automated unit/feature testing.

### 3. Production Deployment Environment
- Hasil akhir aset frontend dari pipeline CI akan disuntikkan menuju server cloud Azure saat proses deployment berlangsung.