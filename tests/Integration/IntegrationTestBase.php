<?php

namespace Tests\Integration;

use App\Models\Role;
use App\Models\User;
use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\RasHewan;
use App\Models\JenisHewan;
use App\Models\RekamMedis;
use App\Models\TemuDokter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BASE CLASS UNTUK INTEGRATION TESTS
 * 
 * Hierarki Top-Down (Mulai dari Skenario Bisnis Utama)
 * =====================================================
 * Level 1: Skenario End-to-End
 *   └─ Level 2: Workflow Spesifik
 *       └─ Level 3: Operasi Teknis
 * 
 * File ini menyediakan helper methods untuk setup dan teardown
 */
abstract class IntegrationTestBase extends TestCase
{
    use RefreshDatabase;

    // ============================================================
    // ACTORS (Pemeran dalam Sistem)
    // ============================================================
    protected User $pemilik;
    protected User $resepsionis;
    protected User $dokter;
    protected User $perawat;
    protected User $admin;

    // ============================================================
    // ROLES
    // ============================================================
    protected Role $rolePemilik;
    protected Role $roleResepsionis;
    protected Role $roleDokter;
    protected Role $rolePerawat;
    protected Role $roleAdmin;

    // ============================================================
    // ROLE_USER MAPPINGS (idrole_user)
    // ============================================================
    protected int $idRoleUserResepsionis;
    protected int $idRoleUserDokter;
    protected int $idRoleUserPerawat;

    // ============================================================
    // MASTER DATA
    // ============================================================
    protected Pet $pet;
    protected Pemilik $pemilikData;
    protected RasHewan $rasHewan;
    protected JenisHewan $jenisHewan;

    /**
     * Setup: Inisialisasi semua data awal untuk testing
     * STEP 1: Create Roles
     * STEP 2: Create Users
     * STEP 3: Create Role-User Mappings
     * STEP 4: Create Master Data
     */
    protected function setUpTestActors(): void
    {
        // STEP 1: Buat Role-role
        $this->roleAdmin = Role::create(['nama_role' => 'Administrator']);
        $this->rolePemilik = Role::create(['nama_role' => 'Pemilik Hewan']);
        $this->roleResepsionis = Role::create(['nama_role' => 'Resepsionis']);
        $this->roleDokter = Role::create(['nama_role' => 'Dokter']);
        $this->rolePerawat = Role::create(['nama_role' => 'Perawat']);

        // STEP 2: Buat Users dengan factory
        $this->admin = User::factory()->create(['nama' => 'Admin Test']);
        $this->pemilik = User::factory()->create(['nama' => 'Pemilik Test']);
        $this->resepsionis = User::factory()->create(['nama' => 'Resepsionis Test']);
        $this->dokter = User::factory()->create(['nama' => 'Dokter Test']);
        $this->perawat = User::factory()->create(['nama' => 'Perawat Test']);

        // STEP 3: Mapping User ke Role (tabel role_user)
        DB::table('role_user')->insert([
            ['iduser' => $this->admin->iduser, 'idrole' => $this->roleAdmin->idrole, 'status' => 1],
            ['iduser' => $this->pemilik->iduser, 'idrole' => $this->rolePemilik->idrole, 'status' => 1],
        ]);

        $this->idRoleUserResepsionis = DB::table('role_user')->insertGetId([
            'iduser' => $this->resepsionis->iduser,
            'idrole' => $this->roleResepsionis->idrole,
            'status' => 1
        ]);

        $this->idRoleUserDokter = DB::table('role_user')->insertGetId([
            'iduser' => $this->dokter->iduser,
            'idrole' => $this->roleDokter->idrole,
            'status' => 1
        ]);

        $this->idRoleUserPerawat = DB::table('role_user')->insertGetId([
            'iduser' => $this->perawat->iduser,
            'idrole' => $this->rolePerawat->idrole,
            'status' => 1
        ]);
    }

    /**
     * Setup: Inisialisasi master data Pet
     */
    protected function setUpMasterData(): void
    {
        // Buat jenis hewan
        $this->jenisHewan = JenisHewan::create([
        'idjenis_hewan' => 1,
        'nama_jenis_hewan' => 'Kucing'
    ]);

        // Buat ras hewan
        $this->rasHewan = RasHewan::create([
        'idras_hewan' => 1,
        'nama_ras_hewan' => 'Persia',
        'idjenis_hewan' => $this->jenisHewan->idjenis_hewan
    ]);

        // Buat data pemilik
        $this->pemilikData = Pemilik::create([
            'nama_pemilik' => 'Budi Santoso',
            'no_telp' => '08123456789',
            'alamat' => 'Jl. Merdeka No. 10'
        ]);

        // Buat pet
        $this->pet = Pet::create([
            'nama' => 'Whiskers',
            'tanggal_lahir' => '2022-01-15',
            'warna_tanda' => 'Orange',
            'jenis_kelamin' => 'Betina',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => 1
        ]);
    }

    // ============================================================
    // WORKFLOW BUILDERS (Membangun Skenario)
    // ============================================================

    /**
     * SKENARIO: Janji temu dibuat (Pending)
     * 
     * State machine:
     * START → [Resepsionis membuat janji] → PENDING
     */
    protected function createPendingAppointment(): TemuDokter
    {
        return TemuDokter::create([
            'idrole_user' => $this->idRoleUserDokter,
            'waktu_daftar' => now()->addDay(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU
        ]);
    }

    /**
     * SKENARIO: Janji temu disetujui (Approved)
     * Status berubah dari PENDING → APPROVED
     * 
     * Ini adalah state transisi sebelum pemeriksaan dimulai
     */
    protected function approveAppointment(TemuDokter $appointment): TemuDokter
    {
        $appointment->update(['status' => TemuDokter::STATUS_MENUNGGU]); // Status masih menunggu dokter
        return $appointment->fresh();
    }

    /**
     * SKENARIO: Dokter melakukan pemeriksaan dan membuat rekam medis
     * 
     * Workflow:
     * 1. Dokter mulai pemeriksaan pada pet
     * 2. Mencatat anamnesa (riwayat keluhan)
     * 3. Mencatat temuan klinis (hasil pemeriksaan fisik)
     * 4. Membuat diagnosa
     * 
     * Hasil: Rekam medis dibuat dengan status DRAFT
     */
    protected function examineAndCreateMedicalRecord(
        TemuDokter $appointment,
        string $anamnesa,
        string $temuanKlinis,
        string $diagnosa
    ): RekamMedis {
        return RekamMedis::create([
            'idpet' => $this->pet->idpet,
            'dokter_pemeriksa' => $this->idRoleUserDokter,
            'anamnesa' => $anamnesa,
            'temuan_klinis' => $temuanKlinis,
            'diagnosa' => $diagnosa,
            'created_at' => now()
        ]);
    }

    /**
     * SKENARIO: Perawat melengkapi rekam medis
     * 
     * Setelah dokter membuat diagnosa, perawat melengkapi catatan:
     * - Resep obat
     * - Instruksi perawatan
     * - Jadwal follow-up
     */
    protected function completeAndVerifyMedicalRecord(RekamMedis $record): RekamMedis
    {
        // Simulasi proses verifikasi/penyelesaian
        $record->update([
            'updated_at' => now()
        ]);
        return $record->fresh();
    }

    /**
     * SKENARIO: Tutup janji temu (Mark as completed)
     * Status: MENUNGGU → SELESAI
     */
    protected function completeAppointment(TemuDokter $appointment): TemuDokter
    {
        $appointment->update(['status' => TemuDokter::STATUS_SELESAI]);
        return $appointment->fresh();
    }

    /**
     * SKENARIO: Cancel janji temu
     * Status: * → BATAL
     */
    protected function cancelAppointment(TemuDokter $appointment, string $reason = 'Dibatalkan oleh pemilik'): TemuDokter
    {
        $appointment->update(['status' => TemuDokter::STATUS_BATAL]);
        return $appointment->fresh();
    }

    // ============================================================
    // ASSERTIONS (Verifikasi State)
    // ============================================================

    /**
     * Verifikasi: Janji temu ada di sistem
     */
    protected function assertAppointmentExists(TemuDokter $appointment): void
    {
        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $appointment->idreservasi_dokter,
            'status' => $appointment->status
        ]);
    }

    /**
     * Verifikasi: Janji temu memiliki status tertentu
     */
    protected function assertAppointmentHasStatus(TemuDokter $appointment, string $expectedStatus): void
    {
        $this->assertEquals($expectedStatus, $appointment->fresh()->status);
    }

    /**
     * Verifikasi: Rekam medis ada di sistem
     */
    protected function assertMedicalRecordExists(RekamMedis $record): void
    {
        $this->assertDatabaseHas('rekam_medis', [
            'idrekam_medis' => $record->idrekam_medis,
            'idpet' => $record->idpet
        ]);
    }

    /**
     * Verifikasi: Rekam medis terhubung dengan dokter yang benar
     */
    protected function assertMedicalRecordAssignedToDoctor(RekamMedis $record, int $idRoleUserDokter): void
    {
        $this->assertEquals($idRoleUserDokter, $record->fresh()->dokter_pemeriksa);
    }

    /**
     * Verifikasi: Pet memiliki rekam medis tertentu
     */
    protected function assertPetHasMedicalRecord(Pet $pet, RekamMedis $record): void
    {
        $this->assertTrue(
            $pet->rekamMedis()->where('idrekam_medis', $record->idrekam_medis)->exists(),
            "Pet tidak memiliki rekam medis yang diharapkan"
        );
    }

    /**
     * Verifikasi: Adanya relasi antara appointment, dokter, dan rekam medis
     */
    protected function assertAppointmentDoesNotHaveAssociatedMedicalRecord(TemuDokter $appointment): void
    {
        // Janji temu yang dibatalkan atau ditolak tidak boleh punya rekam medis
        $this->assertFalse(
            RekamMedis::where('idpet', $this->pet->idpet)
                ->where('created_at', '>=', $appointment->waktu_daftar->subHour())
                ->where('created_at', '<=', $appointment->waktu_daftar->addHour())
                ->exists(),
            "Rekam medis tidak boleh ada untuk appointment yang dibatalkan"
        );
    }
}
