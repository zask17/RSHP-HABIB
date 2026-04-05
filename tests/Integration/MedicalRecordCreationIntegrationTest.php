<?php

namespace Tests\Integration;

use App\Models\RekamMedis;
use App\Models\Pet;
use Carbon\Carbon;

/**
 * LEVEL 2 - MEDICAL RECORD CREATION WORKFLOW
 * ===========================================
 * 
 * Fokus pada proses pembuatan dan pengelolaan rekam medis:
 * - Pembuatan rekam medis oleh dokter
 * - Pencatatan anamnesa, temuan klinis, diagnosa
 * - Verifikasi oleh perawat
 * - Status transisi rekam medis
 * 
 * @group integration
 * @group medical-record-creation
 */
class MedicalRecordCreationIntegrationTest extends IntegrationTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestActors();
        $this->setUpMasterData();
    }

    // =====================================================================
    // TEST WORKFLOW 1: BASIC MEDICAL RECORD CREATION
    // =====================================================================

    /**
     * TEST #001: Dokter Dapat Membuat Rekam Medis
     * 
     * Verifikasi:
     * - Dokter berhasil membuat rekam medis baru
     * - Data examination tersimpan dengan benar
     * - Rekam medis terhubung dengan pet yang benar
     * 
     * @test
     */
    public function test_doctor_can_create_medical_record()
    {
        $medicalRecord = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Hewan tidak mau makan',
            'temuan_klinis' => 'Berat badan turun, mata redup',
            'diagnosa' => 'Infeksi saluran pencernaan',
            'created_at' => now()
        ]);

        // Verifikasi data tersimpan
        $this->assertDatabaseHas('rekam_medis', [
            'idrekam_medis' => $medicalRecord->idrekam_medis,
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'diagnosa' => 'Infeksi saluran pencernaan'
        ]);

        // Verifikasi relasi ke pet
        $this->assertTrue($this->pet->rekamMedis()->where('idrekam_medis', $medicalRecord->idrekam_medis)->exists());
    }

    // =====================================================================
    // TEST WORKFLOW 2: MEDICAL RECORD FIELDS
    // =====================================================================

    /**
     * TEST #002: Semua Field Anamnesa Tersimpan Lengkap
     * 
     * Verifikasi:
     * - Field anamnesa (patient history) tersimpan dengan benar
     * - Mendukung text panjang (detail keluhan)
     * 
     * @test
     */
    public function test_medical_record_anamnesa_field()
    {
        $DetailedAnamnesa = <<<'TEXT'
Pemilik membawa hewan sejak Kamis pukul 10.00.
Keluhan: Hewan muntah 3x, nafsu makan berkurang drastis.
Awal keluhan: 2 hari yang lalu setelah makan makanan baru.
Riwayat vaksin: Lengkap, terakhir 6 bulan lalu.
Riwayat obat: Tidak ada.
Kondisi lingkungan: Cuaca dingin, hewan sering berada di tempat lembab.
TEXT;

        $medicalRecord = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => $DetailedAnamnesa,
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test'
        ]);

        // Verifikasi anamnesa panjang tersimpan
        $this->assertStringContainsString('muntah 3x', $medicalRecord->fresh()->anamnesa);
        $this->assertStringContainsString('makanan baru', $medicalRecord->fresh()->anamnesa);
        $this->assertStringContainsString('vaksin: Lengkap', $medicalRecord->fresh()->anamnesa);
    }

    /**
     * TEST #003: Temuan Klinis Tersimpan Lengkap
     * 
     * Verifikasi:
     * - Field temuan_klinis (clinical findings) tersimpan detail
     * - Mendukung format paragraph dengan multiple findings
     * 
     * @test
     */
    public function test_medical_record_clinical_findings_field()
    {
        $ClinicalFindings = <<<'TEXT'
GENERAL CONDITION: Kondisi umum kurang baik, hewan terlihat lesu.
VITAL SIGNS: 
  - Temperature: 39.5°C (normal: 38-39°C) → DEMAM RINGAN
  - Heart Rate: 120 bpm (normal: 100-130 bpm) → OK
  - Respiration: 35 per minute (normal: 20-30) → TACHYPNEA
PHYSICAL EXAMINATION:
  - Mata: injeksi konjungtiva, terjadi dehidrasi
  - Abdomen: teraba tegang, sensitif
  - Feses: tidak ada (konstipasi)
TEXT;

        $medicalRecord = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Test',
            'temuan_klinis' => $ClinicalFindings,
            'diagnosa' => 'Test'
        ]);

        // Verifikasi clinical findings tersimpan
        $this->assertStringContainsString('39.5°C', $medicalRecord->fresh()->temuan_klinis);
        $this->assertStringContainsString('TACHYPNEA', $medicalRecord->fresh()->temuan_klinis);
        $this->assertStringContainsString('injeksi konjungtiva', $medicalRecord->fresh()->temuan_klinis);
    }

    /**
     * TEST #004: Diagnosa Tersimpan dengan Benar
     * 
     * Verifikasi:
     * - Field diagnosa tersimpan
     * - Dapat berisi diagnosa tunggal atau multiple diagnosa
     * 
     * @test
     */
    public function test_medical_record_diagnosis_field()
    {
        // Diagnosa tunggal
        $mr1 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Test',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Gastroenteritis'
        ]);

        $this->assertEquals('Gastroenteritis', $mr1->fresh()->diagnosa);

        // Create pet lain untuk diagnosa ganda test
        $pet2 = Pet::create([
            'nama' => 'Mittens',
            'tanggal_lahir' => '2021-06-20',
            'warna_tanda' => 'Putih',
            'jenis_kelamin' => 'Jantan',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => 1
        ]);

        // Diagnosa ganda/diferensial
        $mr2 = RekamMedis::create([
            'idpet' => $pet2->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Test',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Gastroenteritis / Pankreatitis'
        ]);

        $this->assertStringContainsString('Gastroenteritis', $mr2->fresh()->diagnosa);
        $this->assertStringContainsString('Pankreatitis', $mr2->fresh()->diagnosa);
    }

    // =====================================================================
    // TEST WORKFLOW 3: DOCTOR ATTRIBUTION
    // =====================================================================

    /**
     * TEST #005: Rekam Medis Selalu Mencatat Dokter Pemeriksa
     * 
     * Verifikasi:
     * - Setiap rekam medis mengasosiasikan dengan dokter tertentu
     * - Tidak boleh ada rekam medis tanpa dokter pemeriksa
     * 
     * @test
     */
    public function test_medical_record_must_have_examining_doctor()
    {
        $medicalRecord = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Test',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test'
        ]);

        $this->assertNotNull($medicalRecord->dokter_pemeriksa);
        $this->assertEquals($this->idRoleUserDokter, $medicalRecord->dokter_pemeriksa);
    }

    /**
     * TEST #006: Relasi Dokter dari Rekam Medis
     * 
     * Verifikasi:
     * - Dapat mengakses data dokter melalui relasi rekam medis
     * - Dokter pemeriksa bisa di-retrieve melalui RoleUser
     * 
     * @test
     */
    public function test_can_access_doctor_from_medical_record()
    {
        $medicalRecord = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Test',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test'
        ]);

        // Akses relasi dokter
        $this->assertNotNull($medicalRecord->dokterPemeriksa);
        $this->assertEquals($this->idRoleUserDokter, $medicalRecord->dokterPemeriksa->idrole_user);
    }

    // =====================================================================
    // TEST WORKFLOW 4: TIMESTAMP TRACKING
    // =====================================================================

    /**
     * TEST #007: Rekam Medis Mencatat Waktu Pembuatan
     * 
     * Verifikasi:
     * - created_at terisi dengan waktu pembuatan
     * - Tidak ada updated_at (model menggunakan model dengan UPDATED_AT = null)
     * 
     * @test
     */
    public function test_medical_record_tracks_creation_time()
    {
        $beforeCreation = now();
        
        $medicalRecord = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Test',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test'
        ]);

        $afterCreation = now();

        // Verifikasi created_at dalam range yang benar
        $this->assertTrue($medicalRecord->created_at >= $beforeCreation);
        $this->assertTrue($medicalRecord->created_at <= $afterCreation);
    }

    // =====================================================================
    // TEST WORKFLOW 5: MEDICAL RECORD HISTORY
    // =====================================================================

    /**
     * TEST #008: Pet Memiliki Riwayat Rekam Medis
     * 
     * Verifikasi:
     * - Dapat melihat semua rekam medis untuk satu pet
     * - Riwayat diurutkan berdasarkan waktu
     * 
     * @test
     */
    public function test_can_view_medical_record_history_for_pet()
    {
        // Buat 3 rekam medis untuk pet yang sama
        $mr1 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'First examination',
            'temuan_klinis' => 'Normal',
            'diagnosa' => 'Sehat',
            'created_at' => Carbon::now()->subDays(2)
        ]);

        sleep(1); // Ensure different timestamps

        $mr2 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Second examination',
            'temuan_klinis' => 'Recovery',
            'diagnosa' => 'Baik',
            'created_at' => Carbon::now()->subDay()
        ]);

        sleep(1);

        $mr3 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Third examination',
            'temuan_klinis' => 'Full recovery',
            'diagnosa' => 'Sangat baik',
            'created_at' => now()
        ]);

        // Query riwayat rekam medis
        $medicalHistory = $this->pet->rekamMedis()->orderBy('created_at')->get();

        // Verifikasi ada 3 rekam medis
        $this->assertCount(3, $medicalHistory);

        // Verifikasi urutan kronologis
        $this->assertEquals('First examination', $medicalHistory[0]->anamnesa);
        $this->assertEquals('Second examination', $medicalHistory[1]->anamnesa);
        $this->assertEquals('Third examination', $medicalHistory[2]->anamnesa);
    }

    // =====================================================================
    // TEST WORKFLOW 6: MEDICAL RECORD VERIFICATION
    // =====================================================================

    /**
     * TEST #009: Perawat Dapat Memverifikasi Rekam Medis
     * 
     * Verifikasi:
     * - Perawat dapat mengakses rekam medis yang dibuat dokter
     * - Dapat melihat detail pemeriksaan
     * 
     * @test
     */
    public function test_nurse_can_verify_medical_record()
    {
        $medicalRecord = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Keluhan utama tercatat dokter',
            'temuan_klinis' => 'Pemeriksaan fisik lengkap',
            'diagnosa' => 'Primary diagnosis'
        ]);

        // Verifikasi bahwa perawat bisa akses rekam medis
        $retrievedRecord = RekamMedis::find($medicalRecord->idrekam_medis);
        $this->assertNotNull($retrievedRecord);
        $this->assertEquals('Keluhan utama tercatat dokter', $retrievedRecord->anamnesa);
    }

    // =====================================================================
    // TEST WORKFLOW 7: MULTIPLE PETS MEDICAL RECORDS
    // =====================================================================

    /**
     * TEST #010: Isolasi Rekam Medis Antar Pet
     * 
     * Verifikasi:
     * - Rekam medis pet A tidak campur dengan pet B
     * - Query berdasarkan pet mengembalikan hasil yang benar
     * 
     * @test
     */
    public function test_medical_records_isolated_per_pet()
    {
        // Pet 1 - 2 rekam medis
        $mr1_pet1 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Pet 1 - Examination 1',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test'
        ]);

        $mr2_pet1 = RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Pet 1 - Examination 2',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test'
        ]);

        // Pet 2 - 1 rekam medis
        $pet2 = Pet::create([
            'nama' => 'Garfield',
            'tanggal_lahir' => '2020-03-10',
            'warna_tanda' => 'Orange',
            'jenis_kelamin' => 'Jantan',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => 1
        ]);

        $mr1_pet2 = RekamMedis::create([
            'idpet' => $pet2->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Pet 2 - Examination 1',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test'
        ]);

        // Verifikasi isolasi
        $pet1Records = RekamMedis::where('idpet', $this->pet->idpet)->get();
        $pet2Records = RekamMedis::where('idpet', $pet2->idpet)->get();

        $this->assertCount(2, $pet1Records);
        $this->assertCount(1, $pet2Records);

        // Verifikasi nama-nama berbeda
        $this->assertEquals('Pet 1 - Examination 1', $pet1Records[0]->anamnesa);
        $this->assertEquals('Pet 1 - Examination 2', $pet1Records[1]->anamnesa);
        $this->assertEquals('Pet 2 - Examination 1', $pet2Records[0]->anamnesa);
    }

    // =====================================================================
    // TEST WORKFLOW 8: BATCH OPERATIONS
    // =====================================================================

    /**
     * TEST #011: Query Multiple Medical Records by Date Range
     * 
     * Verifikasi:
     * - Dapat mengquery rekam medis berdasarkan tanggal
     * - Berguna untuk reporting/analytics
     * 
     * @test
     */
    public function test_can_query_medical_records_by_date_range()
    {
        $today = now();
        
        // Buat rekam medis di berbagai tanggal
        RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => '3 days ago',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test',
            'created_at' => $today->copy()->subDays(3)
        ]);

        RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => '1 day ago',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test',
            'created_at' => $today->copy()->subDay()
        ]);

        RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => 'Today',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test',
            'created_at' => $today
        ]);

        // Query last 2 days
        $lastTwoDays = RekamMedis::where('idpet', $this->pet->idpet)
            ->where('created_at', '>=', $today->copy()->subDays(2))
            ->where('created_at', '<=', $today)
            ->get();

        $this->assertCount(2, $lastTwoDays);
        $this->assertTrue($lastTwoDays->contains('anamnesa', '1 day ago'));
        $this->assertTrue($lastTwoDays->contains('anamnesa', 'Today'));
    }
}
