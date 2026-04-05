<?php

namespace Tests\Integration;

/**
 * TEMPLATE - Gunakan file ini sebagai template untuk membuat integration tests baru
 * 
 * Cara menggunakan:
 * 1. Copy dan rename file ini
 * 2. Update class name dan namespace
 * 3. Implement test methods sesuai kebutuhan
 * 4. Gunakan methods dari IntegrationTestBase untuk setup dan assertions
 */

class TemplateIntegrationTest extends IntegrationTestBase
{
    /**
     * Setup yang dijalankan sebelum setiap test
     * 
     * IntegrationTestBase already handles RefreshDatabase,
     * jadi database selalu fresh untuk setiap test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Inisialisasi actors (pemilik, dokter, resepsionis, dll)
        $this->setUpTestActors();
        
        // Inisialisasi master data (pets, breeds, dll)
        $this->setUpMasterData();
        
        // Tambah custom setup di sini jika diperlukan
        // $this->customSetup();
    }

    // =====================================================================
    // EXAMPLE TEST 1: Simple Happy Path
    // =====================================================================

    /**
     * Test template: Skenario happy path yang sederhana
     * 
     * @test
     */
    public function test_example_simple_scenario()
    {
        // ARRANGE: Setup data
        $appointment = $this->createPendingAppointment();

        // ACT: Perform action
        $medicalRecord = $this->examineAndCreateMedicalRecord(
            appointment: $appointment,
            anamnesa: 'Kucing batuk-batuk',
            temuanKlinis: 'Paru-paru ada abnormalitas',
            diagnosa: 'Bronkitis'
        );

        // ASSERT: Verify state
        $this->assertMedicalRecordExists($medicalRecord);
        $this->assertPetHasMedicalRecord($this->pet, $medicalRecord);
    }

    // =====================================================================
    // EXAMPLE TEST 2: Complex Workflow
    // =====================================================================

    /**
     * Test template: Skenario workflow yang lebih kompleks
     * 
     * @test
     */
    public function test_example_complex_workflow()
    {
        // ─────────────────────────────────────────────────────────────
        // STAGE 1: Setup
        // ─────────────────────────────────────────────────────────────
        
        $appointment = $this->createPendingAppointment();
        $this->assertAppointmentExists($appointment);

        // ─────────────────────────────────────────────────────────────
        // STAGE 2: Process
        // ─────────────────────────────────────────────────────────────
        
        $medicalRecord = $this->examineAndCreateMedicalRecord(
            appointment: $appointment,
            anamnesa: 'Anamnesa panjang dengan details',
            temuanKlinis: 'Temuan klinis detail',
            diagnosa: 'Primary diagnosis'
        );

        $this->completeAppointment($appointment);

        // ─────────────────────────────────────────────────────────────
        // STAGE 3: Verification
        // ─────────────────────────────────────────────────────────────
        
        $this->assertAppointmentHasStatus($appointment, '1'); // SELESAI
        $this->assertMedicalRecordExists($medicalRecord);
    }

    // =====================================================================
    // EXAMPLE TEST 3: Data Isolation
    // =====================================================================

    /**
     * Test template: Verifikasi isolasi data antar entities
     * 
     * @test
     */
    public function test_example_data_isolation()
    {
        // Create first pet appointment and record
        $mr1 = $this->examineAndCreateMedicalRecord(
            appointment: $this->createPendingAppointment(),
            anamnesa: 'Pet 1 examination',
            temuanKlinis: 'Pet 1 findings',
            diagnosa: 'Pet 1 diagnosis'
        );

        // Create second pet
        $pet2 = \App\Models\Pet::create([
            'nama' => 'Pet 2',
            'tanggal_lahir' => '2023-01-01',
            'warna_tanda' => 'White',
            'jenis_kelamin' => 'Jantan',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => 1
        ]);

        // Create second pet appointment and record (beda pet)
        // NOTE: Perlu modify examineAndCreateMedicalRecord atau create manual
        $mr2 = \App\Models\RekamMedis::create([
            'idpet' => $pet2->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Pet 2 examination',
            'temuan_klinis' => 'Pet 2 findings',
            'diagnosa' => 'Pet 2 diagnosis'
        ]);

        // Verify isolation
        $this->assertCount(1, $this->pet->rekamMedis);
        $this->assertCount(1, $pet2->rekamMedis);
        
        $this->assertNotEquals($mr1->idpet, $mr2->idpet);
    }

    // =====================================================================
    // EXAMPLE TEST 4: Status Transitions
    // =====================================================================

    /**
     * Test template: Verifikasi state transitions
     * 
     * @test
     */
    public function test_example_status_transitions()
    {
        $appointment = $this->createPendingAppointment();

        // Initial state
        $this->assertAppointmentHasStatus($appointment, '0'); // MENUNGGU

        // Transition 1: MENUNGGU → SELESAI
        $completedApt = $this->completeAppointment($appointment);
        $this->assertAppointmentHasStatus($completedApt, '1'); // SELESAI

        // Transition 2: Create new appointment and cancel
        $apt2 = $this->createPendingAppointment();
        $cancelledApt = $this->cancelAppointment($apt2);
        $this->assertAppointmentHasStatus($cancelledApt, '2'); // BATAL
    }

    // =====================================================================
    // EXAMPLE TEST 5: Using Helper Assertions
    // =====================================================================

    /**
     * Test template: Demonstrasi berbagai assertions dari base class
     * 
     * @test
     */
    public function test_example_using_helpers()
    {
        $appointment = $this->createPendingAppointment();

        // Assertion 1: Appointment exists
        $this->assertAppointmentExists($appointment);

        // Assertion 2: Status check
        $this->assertAppointmentHasStatus($appointment, '0');

        // Create medical record
        $mr = $this->examineAndCreateMedicalRecord(
            appointment: $appointment,
            anamnesa: 'Test',
            temuanKlinis: 'Test',
            diagnosa: 'Test'
        );

        // Assertion 3: Medical record exists
        $this->assertMedicalRecordExists($mr);

        // Assertion 4: Doctor attribution
        $this->assertMedicalRecordAssignedToDoctor($mr, $this->idRoleUserDokter);

        // Assertion 5: Pet-record relationship
        $this->assertPetHasMedicalRecord($this->pet, $mr);
    }

    // =====================================================================
    // EXAMPLE TEST 6: Data Validation
    // =====================================================================

    /**
     * Test template: Verifikasi validasi data
     * 
     * @test
     */
    public function test_example_data_validation()
    {
        // Test dengan anamnesa panjang
        $longAnamnesa = str_repeat('Lorem ipsum dolor sit amet, ', 100);
        
        $mr = $this->examineAndCreateMedicalRecord(
            appointment: $this->createPendingAppointment(),
            anamnesa: $longAnamnesa,
            temuanKlinis: 'Test findings',
            diagnosa: 'Test diagnosis'
        );

        // Verify data tersimpan dengan benar
        $retrievedMR = \App\Models\RekamMedis::find($mr->idrekam_medis);
        $this->assertEquals(strlen($longAnamnesa), strlen($retrievedMR->anamnesa));
    }

    // =====================================================================
    // EXAMPLE TEST 7: Multiple Records
    // =====================================================================

    /**
     * Test template: Bekerja dengan multiple records
     * 
     * @test
     */
    public function test_example_multiple_records()
    {
        $records = [];

        // Create 5 medical records for same pet
        for ($i = 1; $i <= 5; $i++) {
            $records[] = $this->examineAndCreateMedicalRecord(
                appointment: $this->createPendingAppointment(),
                anamnesa: "Examination $i",
                temuanKlinis: "Findings $i",
                diagnosa: "Diagnosis $i"
            );
        }

        // Verify all records exist
        $this->assertCount(5, $this->pet->rekamMedis);

        // Verify each record is unique
        $diagnoses = $this->pet->rekamMedis()->pluck('diagnosa')->toArray();
        $this->assertCount(5, array_unique($diagnoses));
    }

    // =====================================================================
    // HELPER METHODS (Optional)
    // =====================================================================

    /**
     * Custom helper method untuk setup yang lebih spesifik
     * 
     * Gunakan ini untuk logic yang recurring di banyak tests
     */
    private function customSetup(): void
    {
        // Custom initialization jika diperlukan
    }

    /**
     * Custom helper untuk common operations
     * 
     * @return void
     */
    private function commonOperation(): void
    {
        // Put common test operations here
    }
}
