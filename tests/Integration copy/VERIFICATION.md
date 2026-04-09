# ✅ Integration Tests - Verification Checklist

## Pre-Flight Checklist

Pastikan semua file sudah ada dan siap:

### 📁 File Structure
```
✅ tests/Integration/ folder exists
   ├─ ✅ IntegrationTestBase.php               (Base class)
   ├─ ✅ AppointmentToMedicalRecordIntegrationTest.php
   ├─ ✅ AppointmentSchedulingIntegrationTest.php
   ├─ ✅ MedicalRecordCreationIntegrationTest.php
   ├─ ✅ MedicalRecordFollowUpIntegrationTest.php
   ├─ ✅ TemplateIntegrationTest.php           (Template)
   ├─ ✅ README.md                             (Main documentation)
   ├─ ✅ QUICKSTART.md                         (Quick reference)
   ├─ ✅ INDEX.md                              (File index)
   ├─ ✅ HIERARCHY.md                          (Visual diagrams)
   ├─ ✅ SUMMARY.md                            (Executive summary)
   └─ ✅ START_HERE.md                         (Getting started)
```

### 🔧 Configuration
```
✅ phpunit.xml updated
   - Integration testsuite added
   - Can run: php artisan test --group=integration
```

### 📚 Models & Factories Exist
```
✅ App\Models\TemuDokter.php          (Appointment model)
✅ App\Models\RekamMedis.php          (Medical record model)
✅ App\Models\Pet.php                 (Pet model)
✅ App\Models\User.php                (User model)
✅ App\Models\Role.php                (Role model)
✅ Database\Factories\TemuDokterFactory.php
✅ Database\Factories\RekamMedisFactory.php
✅ Database\Factories\PetFactory.php
✅ Database\Factories\UserFactory.php
```

---

## 🚀 First Run Checklist

### Step 1: Verify Installation
```bash
✅ Check Laravel is installed
   $ php artisan --version
   > Laravel Framework 10.x (or newer)

✅ Check PHPUnit exists
   $ vendor/bin/phpunit --version
   > PHPUnit 10.x (or newer)

✅ Check migrations exist
   $ php artisan migrate:status
   > Should show migration history
```

### Step 2: Run Integration Tests
```bash
# Option A: Run all integration tests
php artisan test tests/Integration

# Option B: Run by group
php artisan test --group=integration

# Option C: Run specific test file
php artisan test tests/Integration/AppointmentToMedicalRecordIntegrationTest.php

# Option D: Run with verbose output
php artisan test tests/Integration --verbose
```

### Step 3: Expected Output
```
✅ Tests should PASS
   PASS  tests/Integration/AppointmentToMedicalRecordIntegrationTest.php (xxx ms)
   ✓ test_complete_appointment_to_medical_record_workflow
   ✓ test_appointment_can_be_cancelled_before_examination
   ✓ test_single_pet_can_have_multiple_medical_records
   ✓ test_single_pet_examined_by_different_doctors
   
   PASS  tests/Integration/AppointmentSchedulingIntegrationTest.php (xxx ms)
   ✓ test_receptionist_can_schedule_appointment
   ... (10 tests total)
   
   PASS  tests/Integration/MedicalRecordCreationIntegrationTest.php (xxx ms)
   ✓ test_doctor_can_create_medical_record
   ... (11 tests total)
   
   PASS  tests/Integration/MedicalRecordFollowUpIntegrationTest.php (xxx ms)
   ✓ test_follow_up_examination_after_treatment
   ... (7 tests total)

   Tests:   32 passed
   Time:    XX.XXs
```

---

## 🎯 Useful Commands

### Run Tests
```bash
# All integration tests
php artisan test tests/Integration

# Specific test class
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php

# Specific test method
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php --filter="test_multiple_appointments_same_doctor_same_day"

# With verbose output for debugging
php artisan test tests/Integration --verbose

# With code coverage
php artisan test tests/Integration --coverage

# Parallel execution (faster)
php artisan test tests/Integration --parallel

# Stop on first failure
php artisan test tests/Integration --stop-on-failure

# Run multiple times (repeat testing)
php artisan test tests/Integration --repeat=5
```

### Debug Tests
```bash
# Run with PsySH debugger
php artisan tinker
>>> User::all();
>>> Pet::all();
>>> TemuDokter::all();
>>> RekamMedis::all();

# Check database directly
php artisan tinker
>>> DB::table('temu_dokter')->get();
>>> DB::table('rekam_medis')->get();

# Run specific test with debug info
php artisan test tests/Integration/YourTest.php --verbose --stop-on-failure
```

---

## 📖 Documentation Reading Order

1. **For Quick Start** (5 min read)
   → [`QUICKSTART.md`](./QUICKSTART.md)
   - Commands to run tests
   - Test files list

2. **For Understanding** (10 min read)
   → [`START_HERE.md`](./START_HERE.md)
   - Overview of what's created
   - File structure
   - Getting started

3. **For Details** (20 min read)
   → [`INDEX.md`](./INDEX.md)
   - Each file explained
   - Test categories
   - Test statistics

4. **For Architecture** (15 min read)
   → [`HIERARCHY.md`](./HIERARCHY.md)
   - Visual diagrams
   - State machines
   - Data models

5. **For Complete Reference** (30 min read)
   → [`README.md`](./README.md)
   - Full documentation
   - Best practices
   - Testing patterns
   - Debugging tips

6. **For Examples** (15 min read)
   → [`TemplateIntegrationTest.php`](./TemplateIntegrationTest.php)
   - 7 example test patterns
   - How to write tests
   - Common patterns

---

## 🧪 Verify Each Test Level

### Level 1: End-to-End (4 tests)
```bash
php artisan test tests/Integration/AppointmentToMedicalRecordIntegrationTest.php

Expected:
✓ test_complete_appointment_to_medical_record_workflow
✓ test_appointment_can_be_cancelled_before_examination
✓ test_single_pet_can_have_multiple_medical_records
✓ test_single_pet_examined_by_different_doctors

Pass: 4 tests
```

### Level 2A: Scheduling (10 tests)
```bash
php artisan test tests/Integration/AppointmentSchedulingIntegrationTest.php

Expected: 10 tests pass
```

### Level 2B: Creation (11 tests)
```bash
php artisan test tests/Integration/MedicalRecordCreationIntegrationTest.php

Expected: 11 tests pass
```

### Level 2C: Follow-up (7 tests)
```bash
php artisan test tests/Integration/MedicalRecordFollowUpIntegrationTest.php

Expected: 7 tests pass
```

---

## 🔍 If Tests Fail

### Issue: "Class not found"
```
✅ Solution: Check namespace in test file
   namespace Tests\Integration;
   
✅ Make sure IntegrationTestBase.php exists
```

### Issue: "Database error"
```
✅ Solution: Ensure migrations are set up
   php artisan migrate:refresh
   
✅ Check if models have proper relationships
```

### Issue: "Factory error"
```
✅ Solution: Verify factories exist
   database/factories/TemuDokterFactory.php
   database/factories/RekamMedisFactory.php
   
✅ Check factory definitions are complete
```

### Issue: "Assertion failed"
```
✅ Solution: Add --verbose flag
   php artisan test tests/Integration --verbose
   
✅ Check test logic for errors
✅ Verify test data setup in setUp() method
```

---

## 📊 Next Steps After Verification

### 1. Understand the Tests
```bash
# Read test files to understand patterns
cat tests/Integration/AppointmentToMedicalRecordIntegrationTest.php

# Try running specific tests
php artisan test tests/Integration/AppointmentToMedicalRecordIntegrationTest.php --filter="test_complete"
```

### 2. Add Custom Tests
```bash
# Copy template
cp tests/Integration/TemplateIntegrationTest.php tests/Integration/YourTest.php

# Edit class name and implement
# Run your test
php artisan test tests/Integration/YourTest.php
```

### 3. Integrate to CI/CD
```bash
# Add to pipeline configuration (GitHub Actions, etc.)
php artisan test --group=integration --parallel

# Or with coverage reporting
php artisan test --group=integration --coverage --coverage-clover coverage.xml
```

### 4. Monitor Test Health
```bash
# Regular test runs
php artisan test tests/Integration

# With coverage
php artisan test tests/Integration --coverage

# Performance check
php artisan test tests/Integration --parallel --profile
```

---

## ✨ Success Checklist

```
✅ All 12 files created successfully
✅ phpunit.xml updated with Integration testsuite
✅ Can run: php artisan test tests/Integration
✅ All 32 tests PASS
✅ Documentation files readable
✅ Template available for new tests
✅ Base class infrastructure working
✅ Workflow builders functional
✅ Custom assertions working

🎉 INTEGRATION TESTS READY! 🎉
```

---

## 📞 Support

If you encounter issues:

1. **Check documentation:**
   - README.md - Main guide
   - HIERARCHY.md - Visual helpful
   - TemplateIntegrationTest.php - Examples

2. **Verify setup:**
   - Check all models exist
   - Verify factories are defined
   - Ensure migrations run

3. **Debug single test:**
   - Run with `--verbose` flag
   - Use `dd()` to inspect state
   - Check database content with tinker

4. **Common issues:**
   - Missing models → Run migrations
   - Factory errors → Check factory definitions
   - Database errors → Use `RefreshDatabase` trait

---

## 🎓 Learning Path

1. **Day 1:** Read START_HERE.md + Run tests
2. **Day 2:** Review AppointmentToMedicalRecordIntegrationTest.php
3. **Day 3:** Study AppointmentSchedulingIntegrationTest.php
4. **Day 4:** Study MedicalRecordCreationIntegrationTest.php
5. **Day 5:** Study MedicalRecordFollowUpIntegrationTest.php
6. **Day 6:** Create your first custom test using template
7. **Day 7:** Add more tests based on your needs

---

**You're ready to go!** 🚀 Run `php artisan test tests/Integration` and enjoy testing!
