# Integration Tests - Appointment & Medical Record System

## 📋 Hierarki Testing (Top-Down Approach)

```
┌─────────────────────────────────────────────────────────────────┐
│ LEVEL 1: END-TO-END INTEGRATION TEST                            │
│ AppointmentToMedicalRecordIntegrationTest.php                   │
│ ─────────────────────────────────────────────────────────────  │
│ Menguji seluruh flow dari pembuatan janji temu hingga            │
│ penyelesaian rekam medis (Happy Path + Edge Cases)              │
└────────────────┬──────────────────────────────────────────────┘
                 │
    ┌────────────┴──────────────┬──────────────────┐
    │                           │                  │
┌───▼──────────────────────┐ ┌─▼──────────────────┐ ┌─▼──────────────────┐
│ LEVEL 2: SCHEDULING      │ │ LEVEL 2: CREATION  │ │ LEVEL 2: FOLLOW-UP │
│ WORKFLOW                 │ │ WORKFLOW           │ │ WORKFLOW           │
│                          │ │                    │ │                    │
│ AppointmentScheduling    │ │ MedicalRecord      │ │ MedicalRecord      │
│ IntegrationTest.php      │ │ CreationIntegration│ │ FollowUpIntegration│
│                          │ │ Test.php           │ │ Test.php           │
├────────────────────────┤ ├──────────────────┤ ├──────────────────┤
│ • Basic Scheduling     │ │ • Record Creation  │ │ • Follow-up Exam   │
│ • Status Transition    │ │ • Data Fields      │ │ • Progress Monitor │
│ • Doctor Queue         │ │ • Doctor Attribution│ │ • Treatment Track │
│ • Time Validation      │ │ • Timestamps       │ │ • Discharge Plan   │
│ • Filtering            │ │ • History Record   │ │ • Hospitalization  │
│ • Workload View        │ │ • Verification     │ │ • Chronic Disease  │
│ • Bulk Operation       │ │ • Batch Operations │ │ • Medication Track │
└────────────────────────┘ └──────────────────┘ └──────────────────┘
```

## 🏗️ Arsitektur Testing

### Base Class: `IntegrationTestBase.php`

Menyediakan infrastruktur umum:

```
IntegrationTestBase (abstract)
├── ACTORS (Setup)
│   ├── setUpTestActors() → Create roles, users, role-user mappings
│   └── setUpMasterData() → Create pets, templates, reference data
│
├── WORKFLOW BUILDERS
│   ├── createPendingAppointment()
│   ├── approveAppointment()
│   ├── examineAndCreateMedicalRecord()
│   ├── completeAndVerifyMedicalRecord()
│   ├── completeAppointment()
│   └── cancelAppointment()
│
└── ASSERTIONS (Verify State)
    ├── assertAppointmentExists()
    ├── assertAppointmentHasStatus()
    ├── assertMedicalRecordExists()
    ├── assertMedicalRecordAssignedToDoctor()
    ├── assertPetHasMedicalRecord()
    └── assertAppointmentDoesNotHaveAssociatedMedicalRecord()
```

## 📝 Test Cases Overview

### Level 1: End-to-End Integration Tests
**File:** `AppointmentToMedicalRecordIntegrationTest.php`

| Test Case | Deskripsi | Status |
|-----------|-----------|--------|
| `test_complete_appointment_to_medical_record_workflow()` | Full happy path: Appointment → Examination → Medical Record → Completion | ✅ |
| `test_appointment_can_be_cancelled_before_examination()` | Pembatalan appointment sebelum pemeriksaan | ✅ |
| `test_single_pet_can_have_multiple_medical_records()` | Satu pet dengan multiple check-ups | ✅ |
| `test_single_pet_examined_by_different_doctors()` | Multiple doctors memeriksa pet yang sama | ✅ |

**Scenario Flow:**
```
1. Resepsionis membuat Janji Temu
   ↓
2. Admin approval (optional)
   ↓
3. Dokter melakukan pemeriksaan
   ├─ Mencatat anamnesa
   ├─ Mencatat temuan klinis
   └─ Membuat diagnosa
   ↓
4. Perawat verifikasi & lengkapi
   ↓
5. Janji temu ditutup/diselesaikan
```

### Level 2A: Appointment Scheduling Workflow
**File:** `AppointmentSchedulingIntegrationTest.php`

| Test Case | Focus Area |
|-----------|-----------|
| `test_receptionist_can_schedule_appointment()` | Basic scheduling operation |
| `test_appointment_transitions_from_pending_to_completed()` | Status: PENDING → COMPLETED |
| `test_appointment_can_be_cancelled()` | Status: PENDING → CANCELLED |
| `test_completed_appointment_cannot_be_cancelled()` | State immutability |
| `test_multiple_appointments_same_doctor_same_day()` | Queue management |
| `test_appointment_requires_active_doctor()` | Doctor validation |
| `test_appointment_cannot_be_in_past()` | Time validation |
| `test_can_filter_appointments_by_status()` | Query filtering |
| `test_can_view_doctor_workload()` | Doctor reporting |
| `test_can_cancel_multiple_appointments_for_doctor()` | Bulk operations |

**State Machine:**
```
┌──────────────────┐
│    MENUNGGU      │ ← Initial state
│   (Pending)      │
└────────┬─────────┘
         │
    ┌────┴─────────────┐
    │                  │
    ▼                  ▼
┌─────────┐      ┌──────────┐
│ SELESAI │      │  BATAL   │
│(Completed)     │(Cancelled)
└─────────┘      └──────────┘
```

### Level 2B: Medical Record Creation Workflow
**File:** `MedicalRecordCreationIntegrationTest.php`

| Test Case | Focus Area |
|-----------|-----------|
| `test_doctor_can_create_medical_record()` | Basic record creation |
| `test_medical_record_anamnesa_field()` | Anamnesa (patient history) field |
| `test_medical_record_clinical_findings_field()` | Clinical findings field |
| `test_medical_record_diagnosis_field()` | Diagnosis documentation |
| `test_medical_record_must_have_examining_doctor()` | Doctor attribution |
| `test_can_access_doctor_from_medical_record()` | Doctor relasi |
| `test_medical_record_tracks_creation_time()` | Timestamp tracking |
| `test_can_view_medical_record_history_for_pet()` | History viewing |
| `test_nurse_can_verify_medical_record()` | Nurse verification |
| `test_medical_records_isolated_per_pet()` | Data isolation |
| `test_can_query_medical_records_by_date_range()` | Date-range querying |

**Record Structure:**
```
Medical Record
├── idpet (referensi ke pet)
├── dokter_pemeriksa (referensi ke doctor)
├── anamnesa (patient history - long text)
├── temuan_klinis (clinical findings - paragraph)
├── diagnosa (diagnosis)
├── created_at (timestamp)
└── (NO updated_at)
```

### Level 2C: Medical Record Follow-Up & Monitoring
**File:** `MedicalRecordFollowUpIntegrationTest.php`

| Test Case | Focus Area |
|-----------|-----------|
| `test_follow_up_examination_after_treatment()` | Post-treatment monitoring |
| `test_long_term_monitoring_with_multiple_followups()` | Multi-visit tracking |
| `test_tracking_positive_vs_negative_treatment_response()` | Treatment response |
| `test_discharge_planning_after_successful_treatment()` | Discharge workflow |
| `test_hospitalization_daily_monitoring()` | Daily monitoring |
| `test_chronic_disease_long_term_management()` | Chronic disease tracking |
| `test_medication_change_documentation()` | Medication change log |

**Monitoring Scenarios:**
```
Initial Examination
    ↓
Treatment Period (5 days)
    ↓
Follow-up Check (Progress Assessment)
    ├─ Positive Response → Continue Treatment
    └─ Negative Response → Adjust Treatment
    ↓
Final Check-up
    └─ Discharge or Escalate
```

## 🚀 Menjalankan Tests

### Run semua integration tests:
```bash
php artisan test --group=integration
```

### Run specific test file:
```bash
php artisan test tests/Integration/AppointmentToMedicalRecordIntegrationTest.php
```

### Run specific test case:
```bash
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php --filter="test_multiple_appointments_same_doctor_same_day"
```

### Run dengan verbose output:
```bash
php artisan test --group=integration --verbose
```

### Run dengan coverage:
```bash
php artisan test --group=integration --coverage
```

## 📊 Coverage Areas

### Appointment System
- ✅ Creation & validation
- ✅ Status transitions
- ✅ Doctor queuing
- ✅ Time-based queries
- ✅ Bulk operations
- ✅ Cancellation handling

### Medical Record System
- ✅ Record creation by doctors
- ✅ Data field completeness
- ✅ Doctor attribution
- ✅ Timestamp tracking
- ✅ Pet-record relationship
- ✅ Historical record retrieval

### Workflow Integration
- ✅ End-to-end appointment to medical record flow
- ✅ Appointment to multiple medical records
- ✅ Different doctors examining same pet
- ✅ Treatment follow-ups
- ✅ Monitoring & progress tracking
- ✅ Discharge management
- ✅ Chronic disease management

## 🔍 Menganalisis Kegagalan Test

1. **Lihat test output:**
   ```bash
   php artisan test tests/Integration/YourTest.php
   ```

2. **Debug dengan tinker:**
   ```bash
   php artisan tinker
   >>> $pet = Pet::find(1);
   >>> $pet->rekamMedis()->get();
   ```

3. **Cek database state:**
   ```bash
   php artisan tinker
   >>> DB::table('temu_dokter')->get();
   >>> DB::table('rekam_medis')->get();
   ```

4. **Rebuild database:**
   ```bash
   php artisan migrate:refresh --seed
   ```

## 📚 Struktur Direktori

```
tests/
├── Integration/
│   ├── IntegrationTestBase.php                    (Base class)
│   ├── AppointmentToMedicalRecordIntegrationTest.php      (Level 1)
│   ├── AppointmentSchedulingIntegrationTest.php  (Level 2A)
│   ├── MedicalRecordCreationIntegrationTest.php  (Level 2B)
│   └── MedicalRecordFollowUpIntegrationTest.php  (Level 2C)
├── Feature/                                       (Existing tests)
├── Unit/                                         (Existing tests)
└── TestCase.php
```

## 🎯 Best Practices

1. **Setiap test independen**: Gunakan `RefreshDatabase` trait
2. **Meaningful assertions**: Jangan hanya `assertTrue(true)`
3. **Setup yang konsisten**: Gunakan `setUpTestActors()` dan `setUpMasterData()`
4. **Workflow builders**: Gunakan helper methods dari base class
5. **Clear test names**: Nama test harus describe behavior yang diuji

## ⚙️ Tips Debugging

### Issue: Data tidak cleanup antar test
**Solution:** Pastikan setiap test class use `RefreshDatabase` trait

### Issue: Relasi tidak bekerja
**Solution:** Pastikan foreign key constraints di migration

### Issue: Factory tidak generate data
**Solution:** Pastikan factory definition lengkap

## 🔄 Maintenance

- Update `IntegrationTestBase` ketika ada perubahan infrastruktur test
- Tambah test baru ketika ada feature baru
- Refactor test ketika ada pattern repetition
- Monitor test execution time - harus < 5 detik per test

## 📈 Next Steps

1. **Add API Integration Tests** menggunakan Laravel's HTTP testing
2. **Add Authorization Tests** untuk role-based access
3. **Add Performance Tests** untuk high-volume scenarios
4. **Add Stress Tests** untuk concurrent appointments
