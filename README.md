# 📚 E-Learning Backend API

Sistem backend E-Learning berbasis API (RESTful) yang dibangun menggunakan framework **Laravel 12**. Project ini dirancang untuk mengelola data akademik, materi pembelajaran, forum diskusi, pengumuman, dan notifikasi dengan sistem hak akses pengguna yang ketat menggunakan **Role-Based Access Control (RBAC)**.

---

## 🚀 Tech Stack

Teknologi utama yang digunakan dalam pengembangan backend ini meliputi:

*   **Core Framework:** [Laravel 12.x](https://laravel.com) (PHP >= 8.2)
*   **Database:** MySQL / PostgreSQL / SQLite (Configurable via `.env`)
*   **Authentication:** [Laravel Sanctum](https://laravel.com/docs/sanctum) (Token-based API Authentication)
*   **Development Tools:**
    *   **Vite 7** & **Tailwind CSS v4** (untuk asset bundling & styling bila diperlukan)
    *   **Concurrently** (untuk menjalankan server, queue, logs, dan vite secara bersamaan)
    *   **Laravel Tinker** (untuk interaksi CLI dengan database)

---

## 📖 Dokumentasi API (Swagger / OpenAPI)

Dokumentasi API lengkap proyek ini telah didefinisikan menggunakan standar **OpenAPI Specification (OAS) 3.0.0** pada file:
👉 [**openapi.yaml**](file:///d:/Project/joki/E-learning/Frontend%20E-learning/Elearning-Backend/openapi.yaml)

### Cara Membaca & Menguji API:
1. **Menggunakan Swagger Editor (Online):**
   * Buka [Swagger Editor](https://editor.swagger.io/).
   * Impor / Salin seluruh isi file `openapi.yaml` ke dalam editor tersebut.
   * Anda dapat melihat skema endpoint secara interaktif dan langsung mengujinya menggunakan tombol **"Try it out"**.
2. **Menggunakan Postman:**
   * Buka Postman.
   * Pilih menu **Import**, kemudian unggah file `openapi.yaml`.
   * Postman akan secara otomatis mengonversi spesifikasi ini menjadi sebuah **API Collection** lengkap dengan contoh *request body* dan responsnya.
3. **Menggunakan Visual Studio Code Extension:**
   * Instal ekstensi **Swagger Viewer** atau **OpenAPI Preview**.
   * Buka file `openapi.yaml` dan tekan `Shift + Alt + P` (atau sesuai shortcut ekstensi) untuk melihat visualisasi grafis dokumentasi API secara langsung dari VS Code.

---

## 🔐 Role-Based Access Control (RBAC)

Proyek ini menerapkan sistem keamanan berbasis peran (role) untuk membatasi akses ke berbagai endpoint API.

### 👥 Peran Pengguna (Roles)
Role didefinisikan menggunakan tipe data `enum` pada kolom `role` di tabel `users`:
1.  **`admin`**: Memiliki hak akses penuh untuk mengelola pengguna (CRUD), kelas, mata pelajaran, serta melihat & mengelola semua data sistem.
2.  **`guru`**: Dapat mengelola kelas yang diampu, mata pelajaran, materi pembelajaran, membuat pengumuman, serta berpartisipasi dalam forum diskusi.
3.  **`siswa`**: Hanya dapat melihat materi pembelajaran, forum diskusi, serta menerima pengumuman dan notifikasi yang ditujukan khusus untuk kelas mereka.

### 🛠️ Implementasi Teknis RBAC
1.  **Helper Methods pada User Model** ([User.php](file:///d:/Project/joki/E-learning/Frontend%20E-learning/Elearning-Backend/app/Models/User.php)):
    *   `hasRole(string $role): bool` – Memeriksa apakah user memiliki role tertentu.
    *   `hasAnyRole(array $roles): bool` – Memeriksa apakah user memiliki salah satu dari daftar role yang diberikan.
2.  **Middleware Kustom** ([RoleMiddleware.php](file:///d:/Project/joki/E-learning/Frontend%20E-learning/Elearning-Backend/app/Http/Middleware/RoleMiddleware.php)):
    Menolak akses dengan respons HTTP `403 Forbidden` jika pengguna tidak memiliki role yang diizinkan untuk mengakses endpoint tersebut.
3.  **Routing Protection** ([api.php](file:///d:/Project/joki/E-learning/Frontend%20E-learning/Elearning-Backend/routes/api.php)):
    *   `->middleware('role:admin')` – Khusus Admin.
    *   `->middleware('role:admin,guru')` – Hanya Admin dan Guru.
    *   `->middleware('role:admin,guru,siswa')` – Dapat diakses oleh semua pengguna yang terotentikasi.

---

## 🗺️ Struktur & Proteksi API Route

Semua endpoint API (kecuali Login) memerlukan header `Authorization: Bearer <token>` setelah melakukan login sukses.

| Group/Sprint | Method | Endpoint | Deskripsi | Hak Akses (RBAC) |
| :--- | :--- | :--- | :--- | :--- |
| **Sprint 1: Auth** | `GET` | `/api/health` | Memeriksa status kesehatan API backend | Public |
| | `POST` | `/api/login` | Login pengguna untuk mendapatkan token | Public |
| | `POST` | `/api/register` | Mendaftarkan akun siswa baru | Public |
| | `POST` | `/api/logout` | Revoke token dan logout | `admin`, `guru`, `siswa` |
| | `GET` | `/api/profile` | Mendapatkan detail profil login | `admin`, `guru`, `siswa` |
| | `PUT` | `/api/profile` | Memperbarui profil login (nama, email, password) | `admin`, `guru`, `siswa` |
| **Sprint 2: User** | `GET/POST/...` | `/api/admin` | CRUD manajemen akun pengguna | `admin` |
| **Sprint 3: Akademik**| `GET` | `/api/kelas` | List kelas (Siswa melihat kelasnya, Guru melihat kelasnya) | `admin`, `guru`, `siswa` |
| | `POST/PUT/DEL`| `/api/kelas` | CRUD data kelas & manajemen siswa | `admin`, `guru` |
| | `POST` | `/api/kelas/join` | Siswa bergabung kelas menggunakan kode kelas | `siswa` |
| | `GET` | `/api/mapel` | List semua mata pelajaran | `admin`, `guru`, `siswa` |
| | `POST/PUT/DEL`| `/api/mapel` | CRUD Mata Pelajaran | `admin`, `guru` |
| **Sprint 4: Materi & Tugas**| `GET` | `/api/materi` | List semua materi pembelajaran | `admin`, `guru`, `siswa` |
| | `GET` | `/api/materi/{id}` | Detail materi tertentu | `admin`, `guru`, `siswa` |
| | `POST/PUT/DEL`| `/api/materi` | CRUD data materi pembelajaran | `admin`, `guru` |
| | `GET` | `/api/tugas` | List tugas (Siswa melihat kelasnya + status dikumpul) | `admin`, `guru`, `siswa` |
| | `POST` | `/api/tugas` | Membuat tugas baru untuk kelas (upload lampiran) | `admin`, `guru` |
| | `DELETE` | `/api/tugas/{id}` | Menghapus data tugas | `admin`, `guru` |
| | `POST` | `/api/tugas/{id}/kumpul` | Siswa mengumpulkan lembar jawaban tugas | `siswa` |
| | `GET` | `/api/pengumpulan/{tugas_id}` | List pengumpulan siswa beserta status & file | `admin`, `guru` |
| | `PUT` | `/api/pengumpulan/{id}/nilai` | Input/update nilai & catatan guru (grading) | `admin`, `guru` |
| **Sprint 5: Forum** | `GET/POST/...` | `/api/forum` | CRUD topik diskusi & komentar | `admin`, `guru`, `siswa` |
| | `GET/PUT/DEL` | `/api/notifikasi`| Manajemen notifikasi user | `admin`, `guru`, `siswa` |
| **Sprint 6: Announce**| `GET` | `/api/pengumuman` | Melihat daftar pengumuman | `admin`, `guru`, `siswa` |
| | `POST/PUT/DEL`| `/api/pengumuman` | CRUD pengumuman kelas/sekolah | `admin`, `guru` |

---

## ⚙️ Cara Menjalankan Project

Ikuti langkah-langkah di bawah ini untuk menjalankan project Elearning-Backend di lingkungan lokal Anda.

### Prasyarat (Prerequisites)
Pastikan Anda sudah menginstal tool berikut di sistem Anda:
*   [PHP >= 8.2](https://www.php.net/downloads)
*   [Composer](https://getcomposer.org)
*   [Node.js & NPM](https://nodejs.org)
*   Database Server (MySQL / Laragon / XAMPP)

### Langkah Installasi & Konfigurasi

#### Opsi 1: Menggunakan Perintah Setup Cepat (Automated)
Proyek ini dilengkapi dengan skrip otomatis dalam `composer.json` yang melakukan instalasi composer dependencies, menyalin berkas `.env`, men-generate App Key, melakukan migrasi database, dan menginstal npm packages secara berurutan.

Jalankan perintah berikut di terminal Anda:
```bash
composer run setup
```

#### Opsi 2: Langkah Manual (Step-by-step)
Jika Anda ingin melakukannya secara manual satu per satu:

1.  **Instal PHP Dependencies:**
    ```bash
    composer install
    ```
2.  **Salin File Lingkungan (Environment File):**
    ```bash
    cp .env.example .env
    ```
3.  **Generate Application Key:**
    ```bash
    php artisan key:generate
    ```
4.  **Konfigurasi Database:**
    Buka file `.env` baru Anda dan sesuaikan koneksi database Anda:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=elearning_db
    DB_USERNAME=root
    DB_PASSWORD=
    ```
    *(Pastikan database `elearning_db` sudah dibuat di MySQL server Anda sebelum lanjut).*
5.  **Jalankan Migrasi & Database Seeder:**
    ```bash
    php artisan migrate --seed
    ```
    Perintah ini akan membuat semua tabel yang dibutuhkan dan mengisi data akun default.
6.  **Instal Node Dependencies & Build Assets:**
    ```bash
    npm install
    npm run build
    ```

---

## 👤 Akun Default untuk Testing

Setelah melakukan seeder (`php artisan migrate --seed`), Anda dapat masuk menggunakan akun default berikut:

| No | Peran (Role) | Email / Username | Password | Deskripsi / Hak Akses |
| :--- | :--- | :--- | :--- | :--- |
| 1 | **Admin** | `admin@sekolah.sch.id` / `admin` | `admin123` | Manajemen User, Kelas, Mapel |
| 2 | **Guru** | `budi.santoso@sekolah.sch.id` / `guru_budi` | `guru123` | Manajemen Materi, Kelas, Pengumuman |
| 3 | **Siswa** | `siti.rahayu@sekolah.sch.id` / `siswa_siti` | `siswa123` | Mengakses Materi, Forum Diskusi |

---

## 🏃 Menjalankan Server Development

Untuk menjalankan server backend Laravel dan queue processor secara bersamaan, jalankan perintah berikut:

```bash
composer run dev
```

Perintah ini menggunakan `concurrently` untuk menyalakan:
1.  **Laravel Development Server** di `http://127.0.0.1:8000`
2.  **Queue Listener** (untuk memproses jobs di background)
3.  **Vite Dev Server** (untuk build assets secara real-time jika diperlukan)

---

## 🔍 Menguji Koneksi ke Server

Untuk memverifikasi apakah server backend Anda telah berjalan dan dapat dihubungi dari lingkungan lokal, Anda dapat menjalankan skrip koneksi berikut di terminal baru:

```bash
node check-connection.js
```

Skrip ini akan mengirimkan HTTP request ke server lokal dan menampilkan visual status koneksi:
*   🟢 **HIJAU / BERHASIL**: Menunjukkan server berjalan normal di port `8000` dan dapat diakses.
*   🔴 **MERAH / GAGAL**: Menunjukkan server belum aktif atau port terblokir (disertai langkah pemecahan masalah).
