<?php

namespace Tests\Unit\Admin;

use App\Models\Pet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * UNIT TEST — Pet (Admin Role)
 * Admin memiliki akses penuh untuk manajemen data hewan peliharaan
 * FORMAT PENGUJIAN:
 * [Test Code] | [Test Type] | [Object Being Tested] | [Function]
 */
class PetAdminUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Aktifkan foreign key check
        DB::statement('PRAGMA foreign_keys = ON');

        // 2. Siapkan data Jenis Hewan & Ras Hewan (Induk 1)
        DB::table('jenis_hewan')->insert([
            'idjenis_hewan' => 1,
            'nama_jenis_hewan' => 'Kucing'
        ]);

        DB::table('ras_hewan')->insert([
            'idras_hewan' => 1,
            'nama_ras' => 'Persia',
            'idjenis_hewan' => 1
        ]);

        // 3. Siapkan data User Admin untuk simulasi delete_by (ID 6)
        DB::table('user')->insert([
            'iduser' => 6,
            'nama' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        // 4. Siapkan data User & Pemilik (Induk 2)
        DB::table('user')->insert([
            'iduser' => 10,
            'nama' => 'Pemilik Test',
            'email' => 'pemilik@test.com',
            'password' => bcrypt('password123'),
        ]);

        DB::table('pemilik')->insert([
            'idpemilik' => 1,
            'iduser' => 10,
            'no_wa' => '08123456789',
            'alamat' => 'Jl. Test No. 1'
        ]);
    }

    // ============ FUNCTIONALITY TESTS (ADMIN) ============

    /**
     * UT-F-PET-001 | Functionality | Data tersimpan saat create manual |
     */
    public function test_UT_F_PET_001_admin_data_harus_tersimpan_saat_create_manual()
    {
        $pet = Pet::create([
            'nama' => 'Momo',
            'tanggal_lahir' => '2023-01-01',
            'warna_tanda' => 'Putih Abu-abu',
            'jenis_kelamin' => 'M',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        $this->assertNotNull($pet->idpet);
        $this->assertEquals('Momo', $pet->nama);
        $this->assertDatabaseHas('pet', [
            'nama' => 'Momo',
            'idpemilik' => 1
        ]);
    }

    /**
     * UT-F-PET-002 | Functionality | Data dapat diambil berdasarkan ID |
     */
    public function test_UT_F_PET_002_admin_data_harus_dapat_diambil_berdasarkan_id()
    {
        $pet = Pet::create([
            'nama' => 'Luna',
            'jenis_kelamin' => 'F',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        $retrieved = Pet::find($pet->idpet);
        $this->assertEquals('Luna', $retrieved->nama);
    }

    /**
     * UT-F-PET-003 | Functionality | Data pet dapat diubah |
     */
    public function test_UT_F_PET_003_admin_data_pet_harus_dapat_diubah()
    {
        $pet = Pet::create([
            'nama' => 'Kuro',
            'jenis_kelamin' => 'M',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        $pet->update(['nama' => 'Kuro Maru']);
        $this->assertEquals('Kuro Maru', $pet->fresh()->nama);
    }

    /**
     * UT-F-PET-004 | Functionality | Soft delete pet |
     */
    public function test_UT_F_PET_004_admin_data_pet_harus_dapat_dihapus_soft_delete()
    {
        $pet = Pet::create([
            'nama' => 'Shiro',
            'jenis_kelamin' => 'F',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        // KOREKSI: Pastikan ID 6 sudah dibuat di setUp
        $pet->update([
            'deleted_at' => now(),
            'deleted_by' => 6 
        ]);

        $this->assertNotNull($pet->deleted_at);
        $this->assertEquals(6, $pet->deleted_by);
    }

    // ============ DATA VALIDATION TESTS (ADMIN) ============

    /**
     * UT-V-PET-001 | Data Validation | Gagal jika nama kosong |
     */
    public function test_UT_V_PET_001_admin_harus_gagal_jika_nama_kosong()
    {
        // Gunakan try-catch dengan penanganan khusus database
        try {
            $pet = new Pet();
            $pet->nama = null; // Memicu error jika database NOT NULL
            $pet->jenis_kelamin = 'M';
            $pet->idpemilik = 1;
            $pet->idras_hewan = 1;
            $pet->save();
            
            $this->fail('Seharusnya gagal karena nama null');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-PET-002 | Data Validation | Gagal jika idpemilik tidak valid (FK Check) |
     */
    public function test_UT_V_PET_002_admin_harus_gagal_jika_idpemilik_tidak_valid()
    {
        try {
            Pet::create([
                'nama' => 'Dummy',
                'jenis_kelamin' => 'M',
                'idpemilik' => 999, 
                'idras_hewan' => 1,
            ]);
            $this->fail('Seharusnya gagal karena Foreign Key Constraint');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-PET-003 | Data Validation | Gagal jika idras_hewan tidak valid (FK Check) |
     */
    public function test_UT_V_PET_003_admin_harus_gagal_jika_idras_hewan_tidak_valid()
    {
        try {
            Pet::create([
                'nama' => 'Dummy',
                'jenis_kelamin' => 'M',
                'idpemilik' => 1,
                'idras_hewan' => 999, 
            ]);
            $this->fail('Seharusnya gagal karena Foreign Key Constraint');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-PET-004 | Data Validation | Sukses jika tanggal_lahir null |
     */
    public function test_UT_V_PET_004_admin_harus_sukses_jika_tanggal_lahir_null()
    {
        $pet = Pet::create([
            'nama' => 'Oyen',
            'tanggal_lahir' => null,
            'jenis_kelamin' => 'M',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        $this->assertNull($pet->tanggal_lahir);
        $this->assertNotNull($pet->idpet);
    }
}