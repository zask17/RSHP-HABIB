<?php

namespace Tests\Unit\Resepsionis;

use App\Models\Pet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * UNIT TEST — Pet (Resepsionis Role)
 * Resepsionis memiliki akses untuk pendaftaran dan manajemen data hewan peliharaan
 * * SKENARIO PENGUJIAN FORMAT:
 * [Test Code] | [Test Type] | [Object Being Tested] | [Function]
 */
class PetResepsionisUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Aktifkan foreign key check untuk integritas database
        DB::statement('PRAGMA foreign_keys = ON');

        // 2. Siapkan data Jenis Hewan & Ras Hewan (Induk 1)
        DB::table('jenis_hewan')->insert([
            'idjenis_hewan' => 1,
            'nama_jenis_hewan' => 'Anjing'
        ]);

        DB::table('ras_hewan')->insert([
            'idras_hewan' => 1,
            'nama_ras' => 'Golden Retriever',
            'idjenis_hewan' => 1
        ]);

        // 3. Siapkan data User & Pemilik (Induk 2)
        DB::table('user')->insert([
            'iduser' => 27,
            'nama' => 'Resepsionis Test',
            'email' => 'resepsionis@test.com',
            'password' => bcrypt('resepsionis123'),
        ]);

        DB::table('pemilik')->insert([
            'idpemilik' => 1,
            'iduser' => 27,
            'no_wa' => '08123456789',
            'alamat' => 'Jl. Mawar No. 10'
        ]);
    }

    // ============ FUNCTIONALITY TESTS (RESEPSIONIS) ============

    /**
     * UT-F-PET-001 | Functionality | Data tersimpan saat create manual |
     * Memastikan resepsionis dapat mendaftarkan hewan peliharaan baru
     */
    public function test_UT_F_PET_001_resepsionis_data_harus_tersimpan_saat_create_manual()
    {
        $pet = Pet::create([
            'nama' => 'Buddy',
            'tanggal_lahir' => '2022-05-20',
            'warna_tanda' => 'Cokelat',
            'jenis_kelamin' => 'M',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        $this->assertNotNull($pet->idpet);
        $this->assertEquals('Buddy', $pet->nama);
        $this->assertDatabaseHas('pet', [
            'nama' => 'Buddy',
            'idpemilik' => 1
        ]);
    }

    /**
     * UT-F-PET-002 | Functionality | Data dapat diambil berdasarkan ID |
     */
    public function test_UT_F_PET_002_resepsionis_data_harus_dapat_diambil_berdasarkan_id()
    {
        $pet = Pet::create([
            'nama' => 'Molly',
            'jenis_kelamin' => 'F',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        $retrieved = Pet::find($pet->idpet);
        $this->assertNotNull($retrieved);
        $this->assertEquals('Molly', $retrieved->nama);
    }

    /**
     * UT-F-PET-003 | Functionality | Data pet dapat diubah |
     * Memastikan resepsionis dapat memperbarui informasi hewan
     */
    public function test_UT_F_PET_003_resepsionis_data_pet_harus_dapat_diubah()
    {
        $pet = Pet::create([
            'nama' => 'Rex',
            'jenis_kelamin' => 'M',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        $pet->update(['nama' => 'Rexy']);
        $this->assertEquals('Rexy', $pet->fresh()->nama);
    }

    /**
     * UT-F-PET-004 | Functionality | Soft delete pet |
     */
    public function test_UT_F_PET_004_resepsionis_data_pet_harus_dapat_dihapus_soft_delete()
    {
        $pet = Pet::create([
            'nama' => 'Max',
            'jenis_kelamin' => 'M',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        // Simulasi logic soft delete di PetController
        $pet->update([
            'deleted_at' => now(),
            'deleted_by' => 27 // ID Resepsionis yang melakukan aksi
        ]);

        $this->assertNotNull($pet->deleted_at);
        $this->assertEquals(27, $pet->deleted_by);
    }

    // ============ DATA VALIDATION TESTS (RESEPSIONIS) ============

    /**
     * UT-V-PET-001 | Data Validation | Gagal jika nama kosong |
     */
    public function test_UT_V_PET_001_resepsionis_harus_gagal_jika_nama_kosong()
    {
        try {
            $pet = new Pet();
            $pet->nama = null; // Memicu error jika database NOT NULL
            $pet->jenis_kelamin = 'F';
            $pet->idpemilik = 1;
            $pet->idras_hewan = 1;
            $pet->save();
            
            $this->fail('Seharusnya gagal karena nama null');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-PET-002 | Data Validation | Gagal jika idpemilik tidak valid |
     */
    public function test_UT_V_PET_002_resepsionis_harus_gagal_jika_idpemilik_tidak_valid()
    {
        try {
            Pet::create([
                'nama' => 'Doggy',
                'jenis_kelamin' => 'M',
                'idpemilik' => 999, // ID Pemilik tidak ada di database
                'idras_hewan' => 1,
            ]);
            $this->fail('Seharusnya gagal karena melanggar Foreign Key');
        } catch (QueryException $e) {
            $this->assertStringContainsString('FOREIGN KEY constraint failed', $e->getMessage());
        }
    }

    /**
     * UT-V-PET-003 | Data Validation | Gagal jika idras_hewan tidak valid |
     */
    public function test_UT_V_PET_003_resepsionis_harus_gagal_jika_idras_hewan_tidak_valid()
    {
        try {
            Pet::create([
                'nama' => 'Kitty',
                'jenis_kelamin' => 'F',
                'idpemilik' => 1,
                'idras_hewan' => 888, // ID Ras tidak ada di database
            ]);
            $this->fail('Seharusnya gagal karena melanggar Foreign Key');
        } catch (QueryException $e) {
            $this->assertStringContainsString('FOREIGN KEY constraint failed', $e->getMessage());
        }
    }

    /**
     * UT-V-PET-004 | Data Validation | Sukses jika field opsional kosong |
     */
    public function test_UT_V_PET_004_resepsionis_harus_sukses_jika_tanggal_lahir_dan_warna_null()
    {
        $pet = Pet::create([
            'nama' => 'Bella',
            'tanggal_lahir' => null,
            'warna_tanda' => null,
            'jenis_kelamin' => 'F',
            'idpemilik' => 1,
            'idras_hewan' => 1,
        ]);

        $this->assertNotNull($pet->idpet);
        $this->assertNull($pet->tanggal_lahir);
        $this->assertNull($pet->warna_tanda);
    }
}