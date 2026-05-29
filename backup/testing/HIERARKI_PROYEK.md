# Hierarki Proyek Sistem Manajemen Klinik Hewan

## 📋 Struktur Direktori Utama

```
root/
├── app/                          # Kode aplikasi utama
│   ├── Console/
│   │   └── Commands/             # Perintah CLI
│   │       └── VerifyUserEmail.php
│   ├── Http/
│   │   ├── Controllers/          # Pengontrol aplikasi
│   │   │   ├── Admin/            # Pengontrol admin
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── DokterController.php
│   │   │   │   ├── JenisHewanController.php
│   │   │   │   ├── PemilikController.php
│   │   │   │   ├── PerawatController.php
│   │   │   │   ├── PetController.php
│   │   │   │   ├── RasHewanController.php
│   │   │   │   ├── RekamMedisController.php
│   │   │   │   ├── RoleController.php
│   │   │   │   ├── TemuDokterController.php
│   │   │   │   ├── TindakanTerapiController.php
│   │   │   │   ├── UserController.php
│   │   │   │   └── UserProfileController.php
│   │   │   ├── Auth/             # Pengontrol autentikasi
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── ConfirmablePasswordController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── EmailVerificationPromptController.php
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   └── VerifyEmailController.php
│   │   │   ├── Data/             # Pengontrol data
│   │   │   │   └── TemuDokterController.php
│   │   │   ├── Site/             # Pengontrol situs publik
│   │   │   │   └── SiteController.php
│   │   │   ├── Controller.php     # Kelas dasar pengontrol
│   │   │   └── ProfileController.php
│   │   ├── Middleware/           # Middleware HTTP
│   │   │   └── CheckRole.php      # Pemeriksaan peran pengguna
│   │   └── Requests/             # Form Request Validation
│   │       ├── Auth/
│   │       │   └── LoginRequest.php
│   │       └── ProfileUpdateRequest.php
│   ├── Models/                   # Model data (Eloquent ORM)
│   │   ├── DetailRekamMedis.php
│   │   ├── Dokter.php
│   │   ├── JenisHewan.php
│   │   ├── Kategori.php
│   │   ├── KategoriKlinis.php
│   │   ├── KodeTindakanTerapi.php
│   │   ├── Pemilik.php
│   │   ├── Perawat.php
│   │   ├── Pet.php
│   │   ├── RasHewan.php
│   │   ├── RekamMedis.php
│   │   ├── Role.php
│   │   ├── RoleUser.php
│   │   ├── TemuDokter.php
│   │   └── User.php
│   ├── Providers/                # Service Providers
│   │   └── AppServiceProvider.php
│   └── Services/                 # Layanan bisnis
│       └── UserProfileService.php
├── bootstrap/                    # File bootstrap aplikasi
│   ├── app.php
│   ├── providers.php
│   └── cache/                    # Cache bootstrap
├── config/                       # File konfigurasi
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── filesystems.php
│   ├── logging.php
│   ├── mail.php
│   ├── queue.php
│   ├── services.php
│   └── session.php
├── database/                     # Database
│   ├── factories/                # Model factories untuk testing
│   │   ├── DokterFactory.php
│   │   ├── JenisHewanFactory.php
│   │   ├── PemilikFactory.php
│   │   ├── PerawatFactory.php
│   │   ├── PetFactory.php
│   │   ├── RasHewanFactory.php
│   │   ├── RekamMedisFactory.php
│   │   ├── RoleFactory.php
│   │   ├── RoleUserFactory.php
│   │   ├── TemuDokterFactory.php
│   │   └── UserFactory.php
│   ├── migrations/               # Migrasi database
│   │   └── 2026_03_14_044741_migrasi_semua.php
│   ├── migrations copy/          # Backup migrasi lama
│   └── seeders/                  # Database seeders
│       ├── DatabaseSeeder.php
│       └── RoleTestSeeder.php
├── public/                       # File publik (web root)
│   ├── index.php                 # Entry point aplikasi
│   ├── .htaccess
│   ├── favicon.ico
│   └── robots.txt
├── resources/                    # Aset dan view
│   ├── css/
│   │   └── app.css
│   ├── js/
│   │   ├── app.js
│   │   └── bootstrap.js
│   └── views/                    # Blade templates
│       ├── auth/                 # View autentikasi
│       ├── admin/                # View admin
│       ├── components/           # Komponen Blade
│       ├── layouts/              # Layout template
│       └── ...
├── routes/                       # Definisi rute
│   ├── api.php
│   ├── channels.php
│   ├── console.php
│   └── web.php
├── storage/                      # File storage
│   ├── app/
│   ├── logs/
│   └── framework/
├── tests/                        # Test suite
│   ├── Feature/
│   └── Unit/
├── backup/                       # Backup file
│   └── [File test lama]
├── .env                          # Variabel environment
├── .gitignore
├── .editorconfig
├── composer.json                 # Dependensi PHP
├── composer.lock
├── package.json                  # Dependensi Node.js
├── package-lock.json
├── phpunit.xml                   # Konfigurasi PHPUnit
├── postcss.config.js             # Konfigurasi PostCSS
├── artisan                        # CLI Laravel
└── README.md
```

## 🏗️ Arsitektur Lapisan

### 1. **Lapisan Presentasi (Views)**
- `resources/views/` - Template Blade untuk UI
- Terdiri dari: Auth, Admin, Components, Layouts

### 2. **Lapisan Kontrol (Controllers)**
- `app/Http/Controllers/` - Menangani request HTTP
- **Admin Controllers** - Manajemen data admin
- **Auth Controllers** - Autentikasi & otorisasi
- **Data Controllers** - API data
- **Site Controllers** - Halaman publik

### 3. **Lapisan Bisnis (Services)**
- `app/Services/` - Logika bisnis
- Contoh: `UserProfileService.php`

### 4. **Lapisan Data (Models)**
- `app/Models/` - Representasi data dengan Eloquent ORM
- **Entitas Utama:**
  - User, Role, RoleUser
  - Dokter, Perawat
  - Pemilik, Pet (Hewan Peliharaan)
  - JenisHewan, RasHewan
  - TemuDokter (Appointment)
  - RekamMedis (Medical Record)
  - DetailRekamMedis
  - Kategori, KategoriKlinis
  - KodeTindakanTerapi

### 5. **Lapisan Middleware**
- `app/Http/Middleware/` - Pemrosesan request
- `CheckRole.php` - Validasi peran pengguna

### 6. **Lapisan Validasi**
- `app/Http/Requests/` - Form request validation
- Contoh: `LoginRequest.php`, `ProfileUpdateRequest.php`

## 📊 Entitas Utama & Relasi

```
User (Pengguna)
├── Role (Peran: Admin, Dokter, Perawat, Pemilik)
├── UserProfile (Profil Pengguna)
└── RoleUser (Relasi Many-to-Many)

Dokter (Dokter Hewan)
├── TemuDokter (Janji Temu)
└── RekamMedis (Rekam Medis)

Perawat (Perawat)
└── RekamMedis (Rekam Medis)

Pemilik (Pemilik Hewan)
└── Pet (Hewan Peliharaan)

Pet (Hewan Peliharaan)
├── JenisHewan (Jenis: Anjing, Kucing, dll)
├── RasHewan (Ras: Poodle, Persia, dll)
├── TemuDokter (Janji Temu)
└── RekamMedis (Rekam Medis)

RekamMedis (Rekam Medis)
├── DetailRekamMedis (Detail Rekam Medis)
├── KategoriKlinis (Kategori Klinis)
└── KodeTindakanTerapi (Kode Tindakan Terapi)
```

## 🔐 Sistem Peran & Izin

- **Admin** - Akses penuh ke semua fitur
- **Dokter** - Manajemen janji temu & rekam medis
- **Perawat** - Pendukung dokter
- **Pemilik** - Melihat data hewan & rekam medis mereka

## 🗄️ Database

- **Migrasi:** `database/migrations/2026_03_14_044741_migrasi_semua.php`
- **Factories:** Untuk generate data testing
- **Seeders:** Untuk populate data awal

## 🧪 Testing

- **Unit Tests** - Test logika bisnis
- **Feature Tests** - Test fitur aplikasi
- **Backup Tests** - File test lama di folder `backup/`

## 📦 Konfigurasi

- **Environment:** `.env`
- **Composer:** `composer.json` (dependensi PHP)
- **NPM:** `package.json` (dependensi Node.js)
- **PHPUnit:** `phpunit.xml`

## 🚀 Entry Point

- **Web:** `public/index.php`
- **CLI:** `artisan`
