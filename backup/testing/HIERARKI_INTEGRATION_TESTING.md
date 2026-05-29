# Hierarki Integration Testing - Sistem Manajemen Klinik Hewan

## 📋 Struktur Integration Testing

```
tests/
├── Integration/                          # Folder Integration Tests
│   ├── Auth/                             # Testing Autentikasi & Otorisasi
│   │   ├── LoginFlowTest.php             # Test alur login lengkap
│   │   ├── RegistrationFlowTest.php      # Test alur registrasi
│   │   ├── EmailVerificationTest.php     # Test verifikasi email
│   │   ├── PasswordResetTest.php         # Test reset password
│   │   └── RoleAuthorizationTest.php     # Test otorisasi berdasarkan role
│   │
│   ├── User/                             # Testing Manajemen User
│   │   ├── UserProfileUpdateTest.php     # Test update profil user
│   │   ├── UserRoleAssignmentTest.php    # Test penugasan role ke user
│   │   ├── UserDeletionTest.php          # Test penghapusan user
│   │   └── UserListingTest.php           # Test listing user dengan filter
│   │
│   ├── Dokter/                           # Testing Manajemen Dokter
│   │   ├── DokterCRUDTest.php            # Test Create, Read, Update, Delete dokter
│   │   ├── DokterScheduleTest.php        # Test jadwal dokter
│   │   ├── DokterAvailabilityTest.php    # Test ketersediaan dokter
│   │   └── DokterListingTest.php         # Test listing dokter
│   │
│   ├── Perawat/                          # Testing Manajemen Perawat
│   │   ├── PerawatCRUDTest.php           # Test CRUD perawat
│   │   ├── PerawatAssignmentTest.php     # Test penugasan perawat ke janji temu
│   │   └── PerawatListingTest.php        # Test listing perawat
│   │
│   ├── Pemilik/                          # Testing Manajemen Pemilik
│   │   ├── PemilikCRUDTest.php           # Test CRUD pemilik
│   │   ├── PemilikContactTest.php        # Test data kontak pemilik
│   │   └── PemilikListingTest.php        # Test listing pemilik
│   │
│   ├── Pet/                              # Testing Manajemen Hewan Peliharaan
│   │   ├── PetCRUDTest.php               # Test CRUD hewan peliharaan
│   │   ├── PetJenisRasTest.php           # Test relasi jenis & ras hewan
│   │   ├── PetOwnerRelationTest.php      # Test relasi pemilik-hewan
│   │   └── PetListingTest.php            # Test listing hewan dengan filter
│   │
│   ├── JenisHewan/                       # Testing Jenis Hewan
│   │   ├── JenisHewanCRUDTest.php        # Test CRUD jenis hewan
│   │   ├── JenisHewanValidationTest.php  # Test validasi jenis hewan
│   │   └── JenisHewanListingTest.php     # Test listing jenis hewan
│   │
│   ├── RasHewan/                         # Testing Ras Hewan
│   │   ├── RasHewanCRUDTest.php          # Test CRUD ras hewan
│   │   ├── RasHewanJenisRelationTest.php # Test relasi ras-jenis hewan
│   │   └── RasHewanListingTest.php       # Test listing ras hewan
│   │
│   ├── TemuDokter/                       # Testing Janji Temu Dokter
│   │   ├── TemuDokterBookingTest.php     # Test pemesanan janji temu
│   │   ├── TemuDokterScheduleTest.php    # Test jadwal janji temu
│   │   ├── TemuDokterCancellationTest.php # Test pembatalan janji temu
│   │   ├── TemuDokterRescheduleTest.php  # Test penjadwalan ulang
│   │   ├── TemuDokterValidationTest.php  # Test validasi janji temu
│   │   ├── TemuDokterConflictTest.php    # Test deteksi konflik jadwal
│   │   ├── TemuDokterNotificationTest.php # Test notifikasi janji temu
│   │   └── TemuDokterListingTest.php     # Test listing janji temu
│   │
│   ├── RekamMedis/                       # Testing Rekam Medis
│   │   ├── RekamMedisCRUDTest.php        # Test CRUD rekam medis
│   │   ├── RekamMedisDetailTest.php      # Test detail rekam medis
│   │   ├── RekamMedisTemuDokterTest.php  # Test relasi rekam medis-janji temu
│   │   ├── RekamMedisHistoryTest.php     # Test riwayat rekam medis
│   │   ├── RekamMedisAccessTest.php      # Test akses rekam medis berdasarkan role
│   │   └── RekamMedisListingTest.php     # Test listing rekam medis
│   │
│   ├── DetailRekamMedis/                 # Testing Detail Rekam Medis
│   │   ├── DetailRekamMedisCRUDTest.php  # Test CRUD detail rekam medis
│   │   ├── DetailRekamMedisTerapiTest.php # Test terapi dalam detail rekam medis
│   │   └── DetailRekamMedisValidationTest.php # Test validasi detail
│   │
│   ├── Kategori/                         # Testing Kategori
│   │   ├── KategoriCRUDTest.php          # Test CRUD kategori
│   │   └── KategoriListingTest.php       # Test listing kategori
│   │
│   ├── KategoriKlinis/                   # Testing Kategori Klinis
│   │   ├── KategoriKlinisCRUDTest.php    # Test CRUD kategori klinis
│   │   └── KategoriKlinisListingTest.php # Test listing kategori klinis
│   │
│   ├── KodeTindakanTerapi/               # Testing Kode Tindakan Terapi
│   │   ├── KodeTindakanTerapiCRUDTest.php # Test CRUD kode tindakan
│   │   └── KodeTindakanTerapiListingTest.php # Test listing kode tindakan
│   │
│   ├── Workflow/                         # Testing Workflow Bisnis
│   │   ├── CompleteAppointmentFlowTest.php # Test alur lengkap janji temu
│   │   ├── MedicalRecordCreationFlowTest.php # Test alur pembuatan rekam medis
│   │   ├── PetRegistrationFlowTest.php   # Test alur registrasi hewan
│   │   └── OwnerOnboardingFlowTest.php   # Test alur onboarding pemilik
│   │
│   ├── Permission/                       # Testing Izin & Akses
│   │   ├── AdminAccessTest.php           # Test akses admin
│   │   ├── DokterAccessTest.php          # Test akses dokter
│   │   ├── PerawatAccessTest.php         # Test akses perawat
│   │   ├── PemilikAccessTest.php         # Test akses pemilik
│   │   └── UnauthorizedAccessTest.php    # Test akses tidak sah
│   │
│   ├── Database/                         # Testing Database & Relasi
│   │   ├── DatabaseMigrationTest.php     # Test migrasi database
│   │   ├── DatabaseSeederTest.php        # Test seeder database
│   │   ├── RelationshipTest.php          # Test relasi antar model
│   │   └── DataIntegrityTest.php         # Test integritas data
│   │
│   ├── API/                              # Testing API Endpoints
│   │   ├── TemuDokterAPITest.php         # Test API janji temu
│   │   ├── RekamMedisAPITest.php         # Test API rekam medis
│   │   ├── PetAPITest.php                # Test API hewan
│   │   ├── UserAPITest.php               # Test API user
│   │   └── ErrorHandlingAPITest.php      # Test error handling API
│   │
│   ├── Validation/                       # Testing Validasi Form
│   │   ├── LoginValidationTest.php       # Test validasi login
│   │   ├── RegistrationValidationTest.php # Test validasi registrasi
│   │   ├── TemuDokterValidationTest.php  # Test validasi janji temu
│   │   ├── RekamMedisValidationTest.php  # Test validasi rekam medis
│   │   └── PetValidationTest.php         # Test validasi hewan
│   │
│   ├── Email/                            # Testing Email & Notifikasi
│   │   ├── VerificationEmailTest.php     # Test email verifikasi
│   │   ├── PasswordResetEmailTest.php    # Test email reset password
│   │   ├── AppointmentNotificationTest.php # Test notifikasi janji temu
│   │   └── ReminderEmailTest.php         # Test email reminder
│   │
│   └── Performance/                      # Testing Performa
│       ├── DatabaseQueryPerformanceTest.php # Test performa query
│       ├── LargeDatasetTest.php          # Test dengan dataset besar
│       └── ConcurrentRequestTest.php     # Test request concurrent
│
├── Feature/                              # Feature Tests (sudah ada)
│   └── [Existing feature tests]
│
└── Unit/                                 # Unit Tests (sudah ada)
    └── [Existing unit tests]
```

## 🔄 Alur Integration Testing

### 1. **Setup & Teardown**
```
┌─────────────────────────────────────────┐
│ Setup Database (Fresh Migration)        │
├─────────────────────────────────────────┤
│ Seed Data Awal (Roles, Categories)      │
├─────────────────────────────────────────┤
│ Create Test Users (Admin, Dokter, dll)  │
├─────────────────────────────────────────┤
│ Run Integration Tests                   │
├─────────────────────────────────────────┤
│ Cleanup & Rollback Database             │
└─────────────────────────────────────────┘
```

### 2. **Test Scenarios Utama**

#### A. **Authentication Flow**
```
User Registration
    ↓
Email Verification
    ↓
User Login
    ↓
Role Assignment
    ↓
Access Control
```

#### B. **Appointment Booking Flow**
```
Owner Login
    ↓
Select Pet
    ↓
Choose Doctor & Time
    ↓
Check Availability
    ↓
Book Appointment
    ↓
Send Notification
    ↓
Create Medical Record
```

#### C. **Medical Record Flow**
```
Appointment Confirmed
    ↓
Doctor Examines Pet
    ↓
Create Medical Record
    ↓
Add Details & Therapy
    ↓
Assign Nurse
    ↓
Save & Archive
```

#### D. **Pet Management Flow**
```
Owner Registration
    ↓
Add Pet (Jenis & Ras)
    ↓
Update Pet Info
    ↓
View Pet History
    ↓
Schedule Appointment
```

## 📊 Test Coverage Matrix

| Modul | CRUD | Validasi | Relasi | Akses | Workflow | API |
|-------|------|----------|--------|-------|----------|-----|
| Auth | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| User | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Dokter | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Perawat | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Pemilik | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Pet | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| JenisHewan | ✓ | ✓ | ✓ | ✓ | - | ✓ |
| RasHewan | ✓ | ✓ | ✓ | ✓ | - | ✓ |
| TemuDokter | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| RekamMedis | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| DetailRekamMedis | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Kategori | ✓ | ✓ | ✓ | ✓ | - | ✓ |
| KategoriKlinis | ✓ | ✓ | ✓ | ✓ | - | ✓ |
| KodeTindakanTerapi | ✓ | ✓ | ✓ | ✓ | - | ✓ |

## 🎯 Prioritas Testing

### Priority 1 (Kritis)
- Authentication & Authorization
- Appointment Booking & Scheduling
- Medical Record Creation & Access
- Database Integrity

### Priority 2 (Tinggi)
- User Management
- Pet Management
- Role-based Access Control
- Email Notifications

### Priority 3 (Sedang)
- Master Data (Jenis, Ras, Kategori)
- API Endpoints
- Form Validation
- Performance

### Priority 4 (Rendah)
- UI Components
- Styling
- Minor Features

## 🧪 Test Data Requirements

### Users
```
- Admin User (1)
- Doctor Users (2-3)
- Nurse Users (2-3)
- Owner Users (5-10)
```

### Pets
```
- Various Species (Anjing, Kucing, Burung, dll)
- Various Breeds
- Different Ages & Weights
```

### Appointments
```
- Scheduled Appointments
- Completed Appointments
- Cancelled Appointments
- Conflicting Appointments
```

### Medical Records
```
- Complete Records
- Partial Records
- Records with Multiple Details
- Records with Therapy
```

## 🔍 Assertion Patterns

### Database Assertions
```php
$this->assertDatabaseHas('table', ['column' => 'value']);
$this->assertDatabaseMissing('table', ['column' => 'value']);
$this->assertDatabaseCount('table', 5);
```

### Response Assertions
```php
$response->assertStatus(200);
$response->assertJson(['key' => 'value']);
$response->assertJsonStructure(['data' => ['id', 'name']]);
```

### Model Assertions
```php
$this->assertInstanceOf(Model::class, $model);
$this->assertTrue($model->relationship()->exists());
```

## 📈 Metrics & Reporting

- **Code Coverage Target:** 80%+
- **Test Execution Time:** < 5 menit
- **Pass Rate Target:** 100%
- **Critical Tests:** Harus pass sebelum deployment

## 🚀 Execution Commands

```bash
# Run semua integration tests
php artisan test tests/Integration

# Run specific test class
php artisan test tests/Integration/Auth/LoginFlowTest

# Run dengan coverage report
php artisan test --coverage tests/Integration

# Run dengan verbose output
php artisan test tests/Integration --verbose

# Run parallel (jika tersedia)
php artisan test tests/Integration --parallel
```

## 📝 Test Template

```php
<?php

namespace Tests\Integration\[Module];

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class [FeatureName]Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup test data
    }

    /** @test */
    public function test_[scenario_description]()
    {
        // Arrange
        $user = User::factory()->create();
        
        // Act
        $response = $this->actingAs($user)->post('/endpoint', []);
        
        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('table', ['column' => 'value']);
    }
}
```

## ✅ Checklist Pre-Deployment

- [ ] Semua integration tests pass
- [ ] Code coverage >= 80%
- [ ] Database migrations verified
- [ ] API endpoints tested
- [ ] Permission & authorization verified
- [ ] Email notifications tested
- [ ] Performance acceptable
- [ ] Error handling verified
