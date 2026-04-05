# Quick Start Guide - Integration Tests

## 🚀 Jalankan semua tests

```bash
# Semua integration tests
php artisan test tests/Integration

# Atau menggunakan group flag
php artisan test --group=integration

# Specific test file
php artisan test tests/Integration/AppointmentToMedicalRecordIntegrationTest.php
```

## 🎯 Test yang Tersedia

### 1. **Appointment to Medical Record (End-to-End)**
```bash
php artisan test tests/Integration/AppointmentToMedicalRecordIntegrationTest.php
```

**Tests:**
- ✅ `test_complete_appointment_to_medical_record_workflow` - Alur lengkap
- ✅ `test_appointment_can_be_cancelled_before_examination` - Pembatalan
- ✅ `test_single_pet_can_have_multiple_medical_records` - Multiple visits
- ✅ `test_single_pet_examined_by_different_doctors` - Multiple doctors

### 2. **Appointment Scheduling Workflow**
```bash
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php
```

**Tests (10 scenarios):**
- Basic scheduling
- Status transitions
- Doctor queuing
- Time validation
- Filtering & searching
- Workload management
- Bulk operations

### 3. **Medical Record Creation Workflow**
```bash
php artisan test tests/Integration/MedicalRecordCreationIntegrationTest.php
```

**Tests (11 scenarios):**
- Record creation
- Field validation (anamnesa, temuan klinis, diagnosa)
- Doctor attribution
- Timestamp tracking
- Historical records
- Nurse verification
- Multi-pet isolation

### 4. **Medical Record Follow-Up Workflow**
```bash
php artisan test tests/Integration/MedicalRecordFollowUpIntegrationTest.php
```

**Tests (7 scenarios):**
- Follow-up examinations
- Long-term monitoring
- Treatment response tracking
- Discharge planning
- Hospitalization monitoring
- Chronic disease management
- Medication tracking

## 📊 Run dengan Output Detail

```bash
# Verbose mode
php artisan test --group=integration --verbose

# Dengan coverage
php artisan test --group=integration --coverage

# Parallel execution (lebih cepat)
php artisan test --group=integration --parallel
```

## 🔍 Run Specific Test

```bash
# Jalankan satu test case saja
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php --filter="test_multiple_appointments_same_doctor_same_day"

# Dengan regex pattern
php artisan test tests/Integration --filter="test_.*_appointment.*"
```

## 📋 Info Lengkap

Baca [README.md](./README.md) untuk:
- Hierarki testing top-down
- Arsitektur testing
- Coverage details
- Debugging tips
- Best practices
