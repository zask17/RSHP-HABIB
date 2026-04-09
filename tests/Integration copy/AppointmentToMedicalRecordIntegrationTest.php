<?php

namespace Tests\Integration;

use App\Models\TemuDokter;
use App\Models\RekamMedis;
use Illuminate\Support\Facades\DB;


/**
 * LEVEL 1 - END-TO-END INTEGRATION TEST
 * =====================================
 * 
 * Skenario Bisnis Utama:
 * "Dari Pendaftaran Janji Temu hingga Penyelesaian Rekam Medis"
 * 
 * Happy Path Flow:
 * 1. Pemilik membawa hewan ke klinik
 * 2. Resepsionis membuat janji temu dengan dokter tertentu
 * 3. Admin/Manager melihat janji temu yang pending
 * 4. Dokter melakukan pemeriksaan pada waktu janji
 * 5. Dokter membuat rekam medis dengan diagnosa
 * 6. Perawat memverifikasi dan melengkapi rekam medis
 * 7. Janji temu ditutup/diselesaikan
 * 8. Pemilik dapat melihat hasil pemeriksaan
 * 
 * @group integration
 * @group appointment-to-medical-record
 */
class AppointmentToMedicalRecordIntegrationTest extends IntegrationTestBase
{
    // =====================================================================
    // SETUP
    // =====================================================================

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestActors();
        $this->setUpMasterData();
    }

    // =====================================================================
    // TEST CASE 1: SKENARIO LENGKAP (HAPPY PATH)
    // =====================================================================

    /**
     * TEST UTAMA #001: End-to-End Workflow
     * 
     * Verifikasi seluruh alur dari pembuatan janji temu hingga penyelesaian
     * rekam medis berhasil dilakukan dengan benar.
     * 
     * @test
     */
    public function test_complete_appointment_to_medical_record_workflow()
    {
        // ─────────────────────────────────────────────────────────────
        // STAGE 1: PEMBUATAN JANJI TEMU
        // ─────────────────────────────────────────────────────────────
        // Resepsionis membuat janji temu dengan dokter
        
        $appointment = $this->createPendingAppointment();
        
        // Verifikasi: Janji temu berhasil dibuat
        $this->assertAppointmentExists($appointment);
        $this->assertAppointmentHasStatus($appointment, TemuDokter::STATUS_MENUNGGU);
        $this->assertNotNull($appointment->idreservasi_dokter);
        $this->assertNotNull($appointment->waktu_daftar);

        // ─────────────────────────────────────────────────────────────
        // STAGE 2: PERSETUJUAN JANJI TEMU (OPTIONAL)
        // ─────────────────────────────────────────────────────────────
        // Admin dapat melihat dan menyetujui janji temu
        
        $approvedAppointment = $this->approveAppointment($appointment);
        $this->assertAppointmentHasStatus($approvedAppointment, TemuDokter::STATUS_MENUNGGU);

        // ─────────────────────────────────────────────────────────────
        // STAGE 3: PEMERIKSAAN DAN PEMBUATAN REKAM MEDIS
        // ─────────────────────────────────────────────────────────────
        // Dokter melakukan pemeriksaan dan membuat rekam medis
        
        $medicalRecord = $this->examineAndCreateMedicalRecord(
            appointment: $appointment,
            anamnesa: 'Kucing muntah dan tidak mau makan selama 2 hari',
            temuanKlinis: 'Berat badan turun 2 kg, mata redup, turgor kulit menurun',
            diagnosa: 'Gastroenteritis'
        );

        // Verifikasi: Rekam medis berhasil dibuat
        $this->assertMedicalRecordExists($medicalRecord);
        $this->assertMedicalRecordAssignedToDoctor($medicalRecord, $this->idRoleUserDokter);
        $this->assertPetHasMedicalRecord($this->pet, $medicalRecord);

        // Verifikasi: Data anamnesa tersimpan dengan benar
        $this->assertEquals('Kucing muntah dan tidak mau makan selama 2 hari', 
            $medicalRecord->fresh()->anamnesa);
        
        // Verifikasi: Data temuan klinis tersimpan dengan benar
        $this->assertStringContainsString('turgor kulit', 
            $medicalRecord->fresh()->temuan_klinis);

        // ─────────────────────────────────────────────────────────────
        // STAGE 4: VERIFIKASI DAN PENYELESAIAN REKAM MEDIS
        // ─────────────────────────────────────────────────────────────
        // Perawat melengkapi dan memverifikasi rekam medis
        
        $completedRecord = $this->completeAndVerifyMedicalRecord($medicalRecord);
        $this->assertMedicalRecordExists($completedRecord);

        // ─────────────────────────────────────────────────────────────
        // STAGE 5: PENYELESAIAN JANJI TEMU
        // ─────────────────────────────────────────────────────────────
        // Janji temu ditutup/diselesaikan
        
        $completedAppointment = $this->completeAppointment($appointment);
        $this->assertAppointmentHasStatus($completedAppointment, TemuDokter::STATUS_SELESAI);

        // ─────────────────────────────────────────────────────────────
        // FINAL VERIFICATION: Integritas Data
        // ─────────────────────────────────────────────────────────────
        // Verifikasi bahwa semua data terintegrasi dengan benar
        
        // Pemeriksaan akhir: Hubungan antara appointment, dokter, pet, dan rekam medis
        $this->assertCount(1, $this->pet->rekamMedis);
        $this->assertTrue($this->pet->rekamMedis()->where('idrekam_medis', $medicalRecord->idrekam_medis)->exists());
    }

    // =====================================================================
    // TEST CASE 2: SCENARIO - PEMBATALAN JANJI TEMU
    // =====================================================================

    /**
     * TEST #002: Pembatalan Janji Temu Sebelum Pemeriksaan
     * 
     * Verifikasi bahwa:
     * - Janji temu dapat dibatalkan sebelum pemeriksaan
     * - Tidak ada rekam medis yang dibuat untuk janji yang dibatalkan
     * 
     * @test
     */
    public function test_appointment_can_be_cancelled_before_examination()
    {
        // ─────────────────────────────────────────────────────────────
        // STAGE 1: Buat janji temu
        // ─────────────────────────────────────────────────────────────
        
        $appointment = $this->createPendingAppointment();
        $this->assertAppointmentHasStatus($appointment, TemuDokter::STATUS_MENUNGGU);

        // ─────────────────────────────────────────────────────────────
        // STAGE 2: Batalkan janji temu
        // ─────────────────────────────────────────────────────────────
        
        $cancelledAppointment = $this->cancelAppointment($appointment, 'Pemilik hewan tidak bisa hadir');
        $this->assertAppointmentHasStatus($cancelledAppointment, TemuDokter::STATUS_BATAL);

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION: Tidak ada rekam medis yang dibuat
        // ─────────────────────────────────────────────────────────────
        
        $this->assertAppointmentDoesNotHaveAssociatedMedicalRecord($cancelledAppointment);
    }

    // =====================================================================
    // TEST CASE 3: SCENARIO - MULTIPLE APPOINTMENTS UNTUK PET YANG SAMA
    // =====================================================================

    /**
     * TEST #003: Satu Pet Dapat Memiliki Multiple Medical Records
     * 
     * Verifikasi bahwa:
     * - Satu pet dapat mengalami multiple appointments (check-up berkala)
     * - Setiap appointment menghasilkan rekam medis yang terpisah
     * - Semua rekam medis terhubung dengan pet yang sama
     * 
     * @test
     */
    public function test_single_pet_can_have_multiple_medical_records()
    {
        // ─────────────────────────────────────────────────────────────
        // APPOINTMENT 1: Check-up awal
        // ─────────────────────────────────────────────────────────────
        
        $appointment1 = $this->createPendingAppointment();
        $medicalRecord1 = $this->examineAndCreateMedicalRecord(
            appointment: $appointment1,
            anamnesa: 'Check-up berkala',
            temuanKlinis: 'Kondisi sehat',
            diagnosa: 'Sehat'
        );
        $this->completeAppointment($appointment1);

        // ─────────────────────────────────────────────────────────────
        // APPOINTMENT 2: Follow-up setelah 1 bulan
        // ─────────────────────────────────────────────────────────────
        
        $appointment2 = $this->createPendingAppointment();
        $medicalRecord2 = $this->examineAndCreateMedicalRecord(
            appointment: $appointment2,
            anamnesa: 'Follow-up rutin',
            temuanKlinis: 'Pasien responsif, nafsu makan baik',
            diagnosa: 'Recovery progress'
        );
        $this->completeAppointment($appointment2);

        // ─────────────────────────────────────────────────────────────
        // APPOINTMENT 3: Emergency visit
        // ─────────────────────────────────────────────────────────────
        
        $appointment3 = $this->createPendingAppointment();
        $medicalRecord3 = $this->examineAndCreateMedicalRecord(
            appointment: $appointment3,
            anamnesa: 'Pasien terbentur pintu',
            temuanKlinis: 'Ada luka di kaki depan',
            diagnosa: 'Luka superfisial'
        );
        $this->completeAppointment($appointment3);

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        // Pet memiliki 3 rekam medis
        $this->assertCount(3, $this->pet->rekamMedis);
        
        // Setiap rekam medis terhubung dengan pet
        $this->assertPetHasMedicalRecord($this->pet, $medicalRecord1);
        $this->assertPetHasMedicalRecord($this->pet, $medicalRecord2);
        $this->assertPetHasMedicalRecord($this->pet, $medicalRecord3);

        // Setiap rekam medis memiliki diagnosa yang berbeda
        $medicalRecord1->refresh();
        $medicalRecord2->refresh();
        $medicalRecord3->refresh();

        $this->assertEquals('Sehat', $medicalRecord1->diagnosa);
        $this->assertEquals('Recovery progress', $medicalRecord2->diagnosa);
        $this->assertEquals('Luka superfisial', $medicalRecord3->diagnosa);
    }

    // =====================================================================
    // TEST CASE 4: SCENARIO - DIFFERENT DOCTORS
    // =====================================================================

    /**
     * TEST #004: Appointment dengan Dokter Berbeda
     * 
     * Verifikasi bahwa:
     * - Satu pet dapat diperiksa oleh dokter yang berbeda
     * - Rekam medis mencatat dokter pemeriksa yang benar
     * 
     * @test
     */
    public function test_single_pet_examined_by_different_doctors()
    {
        // Buat dokter kedua
        $dokter2 = $this->createSecondDoctor();

        // ─────────────────────────────────────────────────────────────
        // APPOINTMENT 1: Dokter 1
        // ─────────────────────────────────────────────────────────────
        
        $appointment1 = TemuDokter::create([
            'idrole_user' => $this->idRoleUserDokter,
            'waktu_daftar' => now()->addDay(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU
        ]);

        $medicalRecord1 = $this->examineAndCreateMedicalRecord(
            appointment: $appointment1,
            anamnesa: 'Keluhan pertama',
            temuanKlinis: 'Pemeriksaan dokter 1',
            diagnosa: 'Diagnosa dokter 1'
        );

        $this->assertMedicalRecordAssignedToDoctor($medicalRecord1, $this->idRoleUserDokter);

        // ─────────────────────────────────────────────────────────────
        // APPOINTMENT 2: Dokter 2 (Second Opinion)
        // ─────────────────────────────────────────────────────────────
        
        $appointment2 = TemuDokter::create([
            'idrole_user' => $dokter2['idRoleUser'],
            'waktu_daftar' => now()->addDays(2),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU
        ]);

        $medicalRecord2 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $dokter2['idRoleUser'],
            'anamnesa' => 'Second opinion',
            'temuan_klinis' => 'Pemeriksaan dokter 2',
            'diagnosa' => 'Diagnosa dokter 2',
            'created_at' => now()
        ]);

        $this->assertMedicalRecordAssignedToDoctor($medicalRecord2, $dokter2['idRoleUser']);

        // ─────────────────────────────────────────────────────────────
        // VERIFICATION
        // ─────────────────────────────────────────────────────────────
        
        $this->assertNotEquals(
            $medicalRecord1->dokter_pemeriksa,
            $medicalRecord2->dokter_pemeriksa,
            "Dua dokter berbeda harus mencatat rekam medis mereka sendiri"
        );
    }

    // =====================================================================
    // HELPER METHODS
    // =====================================================================

    /**
     * Helper: Buat dokter kedua untuk testing
     */
    private function createSecondDoctor(): array
    {
        $dokter2 = $this->dokter; // Reuse atau buat users baru jika perlu
        $idRoleUserDokter2 = DB::table('role_user')->insertGetId([
            'iduser' => $dokter2->iduser,
            'idrole' => $this->roleDokter->idrole,
            'status' => 1
        ]);

        return [
            'user' => $dokter2,
            'idRoleUser' => $idRoleUserDokter2
        ];
    }
}
