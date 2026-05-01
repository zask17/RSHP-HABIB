# Integration Tests - File Structure & Documentation

## 📁 Directory Structure

```
tests/Integration/
├── README.md                                    ← Dokumentasi lengkap
├── QUICKSTART.md                                ← Quick start guide
├── INDEX.md                                     ← File ini
├── TemplateIntegrationTest.php                  ← Template untuk test baru
│
├── IntegrationTestBase.php                      ← BASE CLASS (Infrastructure)
│   ├── Method: setUpTestActors()
│   ├── Method: setUpMasterData()
│   ├── Workflow Builders (createPendingAppointment, etc)
│   └── Assertions (assertAppointmentExists, etc)
│
├── [LEVEL 1] End-to-End Integration
│   └── AppointmentToMedicalRecordIntegrationTest.php
│       ├── test_complete_appointment_to_medical_record_workflow()
│       ├── test_appointment_can_be_cancelled_before_examination()
│       ├── test_single_pet_can_have_multiple_medical_records()
│       └── test_single_pet_examined_by_different_doctors()
│
├── [LEVEL 2A] Appointment Scheduling Workflow
│   └── AppointmentSchedulingIntegrationTest.php
│       ├── test_receptionist_can_schedule_appointment()
│       ├── test_appointment_transitions_from_pending_to_completed()
│       ├── test_appointment_can_be_cancelled()
│       ├── test_completed_appointment_cannot_be_cancelled()
│       ├── test_multiple_appointments_same_doctor_same_day()
│       ├── test_appointment_requires_active_doctor()
│       ├── test_appointment_cannot_be_in_past()
│       ├── test_can_filter_appointments_by_status()
│       ├── test_can_view_doctor_workload()
│       └── test_can_cancel_multiple_appointments_for_doctor()
│
├── [LEVEL 2B] Medical Record Creation Workflow
│   └── MedicalRecordCreationIntegrationTest.php
│       ├── test_doctor_can_create_medical_record()
│       ├── test_medical_record_anamnesa_field()
│       ├── test_medical_record_clinical_findings_field()
│       ├── test_medical_record_diagnosis_field()
│       ├── test_medical_record_must_have_examining_doctor()
│       ├── test_can_access_doctor_from_medical_record()
│       ├── test_medical_record_tracks_creation_time()
│       ├── test_can_view_medical_record_history_for_pet()
│       ├── test_nurse_can_verify_medical_record()
│       ├── test_medical_records_isolated_per_pet()
│       └── test_can_query_medical_records_by_date_range()
│
└── [LEVEL 2C] Medical Record Follow-Up Workflow
    └── MedicalRecordFollowUpIntegrationTest.php
        ├── test_follow_up_examination_after_treatment()
        ├── test_long_term_monitoring_with_multiple_followups()
        ├── test_tracking_positive_vs_negative_treatment_response()
        ├── test_discharge_planning_after_successful_treatment()
        ├── test_hospitalization_daily_monitoring()
        ├── test_chronic_disease_long_term_management()
        └── test_medication_change_documentation()
```

## 📝 File Descriptions

### 1. **IntegrationTestBase.php** [BASE CLASS]
**Tujuan:** Menyediakan infrastruktur umum untuk semua integration tests

**Apa yang disediakan:**

| Komponen | Fungsi |
|----------|--------|
| **Actors Setup** | Create roles, users, dan role-user mappings untuk testing |
| **Master Data** | Create pets, breeds, dan reference data |
| **Workflow Builders** | Methods untuk build common scenarios (appointment, medical record, etc) |
| **Assertions** | Custom assertions untuk verify state |
| **Trait: RefreshDatabase** | Auto cleanup after each test |

**Kapan digunakan:** Extend base class ini untuk setiap test class baru

---

### 2. **AppointmentToMedicalRecordIntegrationTest.php** [LEVEL 1]
**Tujuan:** Menguji end-to-end workflow dari appointment ke medical record

**Test Cases (4):**
1. ✅ Complete workflow happy path
2. ✅ Appointment cancellation handling
3. ✅ Multiple medical records untuk single pet
4. ✅ Different doctors examining same pet

**Focus:** Full business process from start to end

**Jalankan dengan:**
```bash
php artisan test tests/Integration/AppointmentToMedicalRecordIntegrationTest.php
```

---

### 3. **AppointmentSchedulingIntegrationTest.php** [LEVEL 2A]
**Tujuan:** Deep dive into appointment scheduling workflow

**Test Cases (10):**
1. Basic scheduling
2. Status transitions (PENDING → COMPLETED)
3. Status transitions (PENDING → CANCELLED)
4. Immutable states
5. Queue management
6. Doctor validation
7. Time validation
8. Status filtering
9. Doctor workload reporting
10. Bulk cancellation

**Focus:** Appointment lifecycle & operations

**Jalankan dengan:**
```bash
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php
```

---

### 4. **MedicalRecordCreationIntegrationTest.php** [LEVEL 2B]
**Tujuan:** Deep dive into medical record creation workflow

**Test Cases (11):**
1. Record creation
2. Anamnesa field validation
3. Clinical findings field
4. Diagnosis field
5. Doctor attribution requirement
6. Access doctor via relationship
7. Timestamp tracking
8. Medical history viewing
9. Nurse verification access
10. Pet-level isolation
11. Date-range queries

**Focus:** Medical record lifecycle & data integrity

**Jalankan dengan:**
```bash
php artisan test tests/Integration/MedicalRecordCreationIntegrationTest.php
```

---

### 5. **MedicalRecordFollowUpIntegrationTest.php** [LEVEL 2C]
**Tujuan:** Deep dive into follow-up & monitoring workflows

**Test Cases (7):**
1. Post-treatment follow-up
2. Long-term multi-visit monitoring
3. Treatment response tracking (positive/negative)
4. Discharge planning
5. Hospitalization daily tracking
6. Chronic disease long-term management
7. Medication change documentation

**Focus:** Treatment journey & patient progression

**Jalankan dengan:**
```bash
php artisan test tests/Integration/MedicalRecordFollowUpIntegrationTest.php
```

---

### 6. **TemplateIntegrationTest.php** [TEMPLATE]
**Tujuan:** Gunakan sebagai template untuk membuat test baru

**Apa yang ada:**
- Setup template
- 7 contoh test cases
- Different testing patterns
- Helper methods examples
- Comments untuk guidance

**Cara menggunakan:**
1. Copy file ini dan rename
2. Replace class name
3. Implement test methods
4. Gunakan helper methods dari base class

---

## 🎯 Testing Approach: Top-Down

```
START: Business Requirement
    ↓
LEVEL 1 (E2E Test)
    "Appointment should flow to Medical Record"
    └─ Tests complete workflow with happy path + main edge cases
    
    ↓
LEVEL 2A (Appointment Workflow)
    "Appointment scheduling must handle status transitions"
    └─ Deep tests for each transition, validation, querying
    
    ↓
LEVEL 2B (Medical Record Creation)
    "Medical record must capture complete examination data"
    └─ Deep tests for each field, data integrity, relationships
    
    ↓
LEVEL 2C (Follow-up Workflow)
    "Medical records must support monitoring and updates"
    └─ Deep tests for multi-visit scenarios, treatment tracking
    
    ↓
OUTPUT: Comprehensive Coverage
```

## 🚀 Quick Commands Reference

```bash
# Run all integration tests
php artisan test tests/Integration

# Run specific test class
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php

# Run specific test method
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php --filter="test_multiple_appointments_same_doctor_same_day"

# Run with verbose output
php artisan test tests/Integration --verbose

# Run with coverage report
php artisan test tests/Integration --coverage

# Run parallel (faster)
php artisan test tests/Integration --parallel

# Run using group flag
php artisan test --group=integration
```

## 📊 Test Statistics

| Category | Count |
|----------|-------|
| Level 1 E2E Tests | 4 tests |
| Level 2A Scheduling Tests | 10 tests |
| Level 2B Creation Tests | 11 tests |
| Level 2C Follow-up Tests | 7 tests |
| **Total Integration Tests** | **32 tests** |

**Coverage Areas:**
- ✅ 40+ different business scenarios
- ✅ Appointment lifecycle (creation → completion/cancellation)
- ✅ Medical record lifecycle (creation → verification → follow-up)
- ✅ Multi-visit patient management
- ✅ Doctor workload & scheduling
- ✅ Data integrity & isolation
- ✅ Status transitions & state management
- ✅ Treatment response tracking
- ✅ Chronic disease management
- ✅ Hospitalization workflows

## 🔗 Relationships Tested

```
         User
         / | \
      /    |    \
  Dokter  Admin  Pemilik
     |     |       |
 RoleUser  |    Pet
     |     |    /   \
     └──────┘  /     \
         |            \
      Appointment  RekamMedis
            |         / | \
            |        /  |  \
            └──────/    |   Dokter
                       /   
                 (multiple records)
```

## 💡 Design Patterns Used

1. **AAA Pattern (Arrange-Act-Assert)**
   - Clear setup → action → verification

2. **Builder Pattern (Workflow Builders)**
   - Helper methods to build complex scenarios

3. **Factory Pattern (Test Data)**
   - Using Laravel factories for data generation

4. **Trait Pattern (RefreshDatabase)**
   - Automatic database cleanup

5. **Inheritance Pattern (Base Class)**
   - Shared methods & common functionality

## 🔍 How to Debug Failing Tests

1. **Read the error message carefully**
2. **Use `--verbose` flag** to see more details
3. **Run single test** to isolate problem
4. **Add `dd()` or `dump()`** in test to inspect state
5. **Check if data exists in database** after test runs
6. **Verify relationships** between models

## 📚 Additional Resources

- [README.md](./README.md) - Full documentation
- [QUICKSTART.md](./QUICKSTART.md) - Quick reference
- [TemplateIntegrationTest.php](./TemplateIntegrationTest.php) - Examples

## ✅ Checklist untuk Menambah Test Baru

- [ ] Extend `IntegrationTestBase`
- [ ] Setup actors dengan `setUpTestActors()`
- [ ] Setup master data dengan `setUpMasterData()`
- [ ] Use workflow builders untuk create scenarios
- [ ] Use assertions dari base class
- [ ] Add clear test description comments
- [ ] Run test locally: `php artisan test path/to/test`
- [ ] Verify test passes
- [ ] Add to documentation
