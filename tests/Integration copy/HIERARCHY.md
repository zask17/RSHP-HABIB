# Integration Test Hierarchy - Visual Guide

## 🏗️ Complete Testing Pyramid

```
                            🎯 BUSINESS OUTCOMES
                            =====================
                                    
                    ┌─────────────────────────────────────┐
                    │  LEVEL 1: END-TO-END INTEGRATION   │
                    │     (Appointment → Medical Record)   │
                    │                                       │
                    │  • Happy Path Flow                   │
                    │  • Cancellation Scenarios            │
                    │  • Multiple Records Per Pet          │
                    │  • Different Doctors                 │
                    │                                       │
                    │  4 Test Cases                        │
                    │  Coverage: 100% of main workflow     │
                    └────────┬────────────────────────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
    ┌───────▼────────┐  ┌───▼────────┐  ┌───▼──────────┐
    │   LEVEL 2A:    │  │ LEVEL 2B:  │  │  LEVEL 2C:   │
    │ SCHEDULING     │  │  CREATION  │  │  FOLLOW-UP   │
    │ WORKFLOW       │  │ WORKFLOW   │  │  WORKFLOW    │
    │                │  │            │  │              │
    │ • Scheduling   │  │ • Records  │  │ • Follow-ups │
    │ • Status Flow  │  │ • Fields   │  │ • Monitoring │
    │ • Queuing      │  │ • Doctor   │  │ • Treatment  │
    │ • Filtering    │  │   Link     │  │ • Discharge  │
    │ • Workload     │  │ • History  │  │ • Chronic    │
    │ • Bulk Ops     │  │ • Verify   │  │   Disease    │
    │                │  │            │  │              │
    │ 10 Tests       │  │ 11 Tests   │  │ 7 Tests      │
    └────────────────┘  └────────────┘  └──────────────┘
            │                │                │
            └────────────────┼────────────────┘
                             │
                             ▼
              🧪 TOTAL: 32 INTEGRATION TESTS
              📊 40+ Different Scenarios
              ✅ Full Workflow Coverage
```

## 🎭 Actors in the System

```
┌──────────────────────────────────────────────────────────┐
│ TEST ACTORS (Roles in System)                           │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  👨‍⚕️  DOKTER (Doctor)                                     │
│      └─ Can create appointment, examine pet            │
│      └─ Can create medical records                     │
│      └─ Assigned to temu_dokter                        │
│                                                          │
│  👩‍⚕️  PERAWAT (Nurse)                                     │
│      └─ Can verify medical records                     │
│      └─ Can complete/finalize records                  │
│                                                          │
│  📋 RESEPSIONIS (Receptionist)                           │
│      └─ Can create appointments                        │
│      └─ Can manage appointment scheduling              │
│                                                          │
│  👤 PEMILIK (Pet Owner)                                  │
│      └─ Pet owner role for authorization               │
│      └─ Linked to pet via pemilik table                │
│                                                          │
│  🔑 ADMIN                                               │
│      └─ Can view/manage all data                       │
│      └─ Can approve appointments (optional)            │
│                                                          │
└──────────────────────────────────────────────────────────┘
```

## 📊 Test Data Model

```
┌─────────────────────────────────────────────────────────────┐
│                      DATABASE ENTITIES                      │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  USER                          ROLE                         │
│  ├─ iduser          PK         ├─ idrole         PK         │
│  ├─ nama                        ├─ nama_role                │
│  ├─ email                       └─ [5 roles]                │
│  └─ ...             FK                                      │
│       └──┐                                                   │
│          │    ROLE_USER                                     │
│          │    ├─ idrole_user    PK                         │
│          └────┤─ iduser        FK → USER                    │
│               ├─ idrole        FK → ROLE                    │
│               └─ status                                      │
│                    │                                        │
│    ┌───────────────┴────────────────┐                      │
│    │ (Maps user to their roles)     │                      │
│    │ Used in: TemuDokter,           │                      │
│    │ RekamMedis (dokter fields)     │                      │
│    │                                │                      │
│    └────────────────────────────────┘                      │
│                                                             │
│  PET                           PEMILIK                      │
│  ├─ idpet         PK           ├─ idpemilik   PK            │
│  ├─ nama                        ├─ nama_pemilik             │
│  ├─ tanggal_lahir              ├─ no_telp                  │
│  ├─ jenis_kelamin              └─ alamat                    │
│  ├─ idpemilik    FK ───────────┘                           │
│  ├─ idras_hewan  FK ─────────┐                             │
│  └─ ...                       │                             │
│    │                    RAS_HEWAN                          │
│    │                    ├─ idras_hewan  PK                 │
│    │                    ├─ nama_ras_hewan                  │
│    │                    └─ idjenis_hewan (JENIS_HEWAN)    │
│    │                                                        │
│    └──────────── [1:N RELATIONSHIPS] ───────────┐          │
│                                                  ▼          │
│  TEMU_DOKTER                    REKAM_MEDIS                │
│  ├─ idreservasi_dokter PK       ├─ idrekam_medis    PK    │
│  ├─ idrole_user        FK       ├─ idpet           FK     │
│  ├─ waktu_daftar                ├─ dokter_pemeriksa FK    │
│  ├─ no_urut                      ├─ anamnesa                │
│  ├─ status (enum)                ├─ temuan_klinis          │
│  └─ timestamps                   ├─ diagnosa               │
│     (0=Menunggu,                 └─ created_at            │
│      1=Selesai,                     (no updated_at)       │
│      2=Batal)                                             │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

## 🔄 Workflow State Machines

### Appointment Status Flow
```
    ┌──────────────────────┐
    │   MENUNGGU (0)       │
    │ [Pending]            │
    │ Initial State        │
    └─────────┬────────────┘
              │
    ┌─────────┴──────────────┐
    │                        │
    ▼                        ▼
┌──────────┐          ┌──────────┐
│ SELESAI  │          │  BATAL   │
│   (1)    │          │   (2)    │
│Completed │          │Cancelled │
└──────────┘          └──────────┘

Valid Transitions:
MENUNGGU → SELESAI ✓
MENUNGGU → BATAL ✓
SELESAI → BATAL ✗ (Not allowed)
SELESAI → MENUNGGU ✗ (Not allowed)
```

### Medical Record Lifecycle
```
┌─────────────────────────────────────────────────────┐
│  Medical Record (1:1 with Appointment, mostly)     │
│  • Created by Doctor during examination           │
│  • Contains: anamnesa, temuan_klinis, diagnosa    │
│  • Verified by Nurse                              │
│  • Timestamp: created_at (immutable after creation)│
│                                                     │
│  Transitions: DRAFT → VERIFIED → ARCHIVED         │
│  (Not explicitly in current DB schema, but        │
│   represented in diagnosa field progression)      │
└─────────────────────────────────────────────────────┘
```

## 📐 Test Execution Flow

```
┌─────────────────────────────────────────────────────────┐
│ PHPUnit Execution (RefreshDatabase trait)              │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Before Each Test:                                      │
│  ├─ Refresh database (schema + empty)                 │
│  ├─ Run migrations                                     │
│  └─ Ready for test                                     │
│                                                          │
│  During Test:                                          │
│  ├─ setUp() called                                     │
│  │  ├─ setUpTestActors()  → Create users/roles       │
│  │  └─ setUpMasterData()  → Create master refs       │
│  ├─ Test method runs                                   │
│  │  ├─ Workflow builders → Create scenarios          │
│  │  ├─ Assertions        → Verify state              │
│  │  └─ Database changes  → Recorded in test DB       │
│  └─ Test completes                                     │
│                                                          │
│  After Each Test:                                      │
│  ├─ Assertions verified                               │
│  ├─ Database rolled back/refreshed                    │
│  └─ Ready for next test (clean state)                 │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## 🎯 Coverage Matrix

```
┌───────────────────────────────────────────────────────────────┐
│         TEST COVERAGE BY BUSINESS PROCESS                    │
├──────────────────────┬──────┬──────┬──────┬──────┬──────────┤
│ Process              │ L1E2E│ L2A  │ L2B  │ L2C  │ TOTAL   │
├──────────────────────┼──────┼──────┼──────┼──────┼──────────┤
│ Scheduling           │  1   │  10  │  -   │  -   │   11    │
│ Record Creation      │  2   │  -   │  11  │  -   │   13    │
│ Follow-up/Monitoring │  1   │  -   │  -   │  7   │    8    │
│ Multi-Doctor         │  1   │  -   │  -   │  -   │    1    │
├──────────────────────┼──────┼──────┼──────┼──────┼──────────┤
│ TOTAL TESTS          │  4   │  10  │  11  │  7   │   32    │
└──────────────────────┴──────┴──────┴──────┴──────┴──────────┘

Legend:
L1E2E = Level 1 End-to-End
L2A   = Level 2A Scheduling
L2B   = Level 2B Creation
L2C   = Level 2C Follow-up
```

## 🧩 Data Organization Patterns

### Test Isolation Per Pet
```
Pet: Whiskers (idpet=1)
├─ Medical Records
│  ├─ Record 1: Initial examination (anamnesa: "...")
│  ├─ Record 2: Follow-up (anamnesa: "...")
│  └─ Record 3: Final evaluation (anamnesa: "...")
├─ Appointments
│  ├─ Appointment 1: Initial visit
│  ├─ Appointment 2: Follow-up visit
│  └─ Appointment 3: Final check

Pet: Garfield (idpet=2)
├─ Medical Records
│  ├─ Record 4: Different pet, different records
│  └─ ...
└─ Appointments
   ...

[NO DATA MIXING BETWEEN PETS]
```

### Timeline Organization
```
Timeline for Test Case:
  Day 0    Day 5    Day 10   Day 15
  │        │        │        │
  ├─ Initial Exam
  │        │
  │        ├─ Follow-up 1 (Progress check)
  │        │
  │                 ├─ Follow-up 2 (Treatment response)
  │                 │
  │                          ├─ Final Check (Discharge)
  │                          │
  └──────────────────────────┘
```

## 📋 Assertion Categories

```
┌─ EXISTENCE ASSERTIONS
│  ├─ assertAppointmentExists()
│  └─ assertMedicalRecordExists()
│
├─ STATE ASSERTIONS
│  ├─ assertAppointmentHasStatus()
│  └─ assertMedicalRecordAssignedToDoctor()
│
├─ RELATIONSHIP ASSERTIONS
│  ├─ assertPetHasMedicalRecord()
│  └─ assertMedicalRecordAssignedToDoctor()
│
└─ NEGATIVE ASSERTIONS
   └─ assertAppointmentDoesNotHaveAssociatedMedicalRecord()
```

## ✨ Key Design Principles

```
1. TOP-DOWN APPROACH
   Business Flow → Scheduling Details → Recording Details → Follow-ups

2. LAYERED TESTING
   E2E (Level 1) → Workflow (Level 2) → Detailed Scenarios

3. REUSABILITY
   Base class provides common infrastructure
   Workflow builders for repeated scenarios
   Assertions for common verifications

4. ISOLATION
   Each test is independent
   RefreshDatabase ensures clean state
   No test dependencies

5. CLARITY
   Clear test names describe behavior
   Stage comments show workflow progression
   Assertions verify expectations explicitly

6. MAINTAINABILITY
   Centralized setup logic in base class
   Helper methods for common operations
   Template for adding new tests
```

---

**Ready to run integration tests?** 
→ See [QUICKSTART.md](./QUICKSTART.md) for commands
→ See [README.md](./README.md) for detailed documentation
