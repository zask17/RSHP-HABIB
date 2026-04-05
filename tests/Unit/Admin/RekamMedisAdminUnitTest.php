<?php

namespace Tests\Unit\Admin;

use App\Models\RekamMedis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * UNIT TEST — Rekam Medis (Admin Role)
 * Admin memiliki akses penuh untuk manajemen rekam medis
 * * SKENARIO PENGUJIAN FORMAT:
 * [Test Code] | [Test Type] | [Object Being Tested] | [Function]
 */
class RekamMedisAdminUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Aktifkan foreign key check untuk integritas database testing
        DB::statement('PRAGMA foreign_keys = ON');

        // 2. Siapkan data Master (Hewan & Role)
        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Anjing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Bulldog', 'idjenis_hewan' => 1]);
        DB::table('role')->insert(['idrole' => 1, 'nama_role' => 'Administrator']);

        // 3. Siapkan data User & Pemilik
        DB::table('user')->insert([
            'iduser' => 6, 
            'nama' => 'Admin User', 
            'email' => 'admin@test.com', 
            'password' => bcrypt('pass123')
        ]);
        DB::table('role_user')->insert(['idrole_user' => 1, 'iduser' => 6, 'idrole' => 1]);
        
        DB::table('pemilik')->insert([
            'idpemilik' => 1, 
            'iduser' => 6, 
            'no_wa' => '0812345', 
            'alamat' => 'Alamat Test'
        ]);

        // 4. Siapkan data Pet (Induk Rekam Medis)
        DB::table('pet')->insert([
            'idpet' => 1,
            'nama' => 'Doggo',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'M'
        ]);

        // 5. Siapkan data Temu Dokter
        DB::table('temu_dokter')->insert([
            'idreservasi_dokter' => 1,
            'no_urut' => 1,
            'idrole_user' => 1,
            'waktu_daftar' => now(), // Field krusial untuk validasi database
            'status' => '1'
        ]);
    }

    // ============ FUNCTIONALITY TESTS (ADMIN) ============

    /**
     * UT-F-RM-001 | Functionality | Create Rekam Medis |
     * Menggunakan forceCreate untuk bypass $fillable karena idreservasi_dokter diproteksi
     */
    public function test_UT_F_RM_001_admin_data_rekam_medis_harus_tersimpan()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Nafsu makan berkurang',
            'temuan_klinis' => 'Suhu tubuh 39C',
            'diagnosa' => 'Demam ringan',
            'idpet' => 1,
            'dokter_pemeriksa' => 1,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $this->assertNotNull($rm->idrekam_medis);
        $this->assertDatabaseHas('rekam_medis', ['diagnosa' => 'Demam ringan']);
    }

    /**
     * UT-F-RM-002 | Functionality | Get Rekam Medis by ID |
     */
    public function test_UT_F_RM_002_admin_data_rekam_medis_harus_dapat_diambil_berdasarkan_id()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Test',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Flu',
            'idpet' => 1,
            'dokter_pemeriksa' => 1,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $retrieved = RekamMedis::find($rm->idrekam_medis);
        $this->assertEquals('Flu', $retrieved->diagnosa);
    }

    /**
     * UT-F-RM-003 | Functionality | Update Rekam Medis |
     */
    public function test_UT_F_RM_003_admin_data_rekam_medis_harus_dapat_diubah()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Lama',
            'temuan_klinis' => 'Lama',
            'diagnosa' => 'Lama',
            'idpet' => 1,
            'dokter_pemeriksa' => 1,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $rm->update(['diagnosa' => 'Diagnosa Baru']);
        $this->assertEquals('Diagnosa Baru', $rm->fresh()->diagnosa);
    }

    /**
     * UT-F-RM-004 | Functionality | Delete Rekam Medis |
     */
    public function test_UT_F_RM_004_admin_data_rekam_medis_harus_dapat_dihapus()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Hapus',
            'temuan_klinis' => 'Hapus',
            'diagnosa' => 'Hapus',
            'idpet' => 1,
            'dokter_pemeriksa' => 1,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $id = $rm->idrekam_medis;
        $rm->delete();

        $this->assertNull(RekamMedis::find($id));
    }

    /**
     * UT-F-RM-005 | Functionality | Relationship to Pet |
     */
    public function test_UT_F_RM_005_admin_rekam_medis_harus_terhubung_ke_pet()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Cek',
            'temuan_klinis' => 'Cek',
            'diagnosa' => 'Sehat',
            'idpet' => 1,
            'dokter_pemeriksa' => 1,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $this->assertEquals('Doggo', $rm->pet->nama);
    }

    // ============ DATA VALIDATION TESTS (ADMIN) ============

    /**
     * UT-V-RM-001 | Data Validation | Gagal jika idpet kosong |
     */
    public function test_UT_V_RM_001_admin_harus_gagal_jika_idpet_kosong()
    {
        try {
            $rm = new RekamMedis();
            $rm->idpet = null; 
            $rm->dokter_pemeriksa = 1;
            $rm->idreservasi_dokter = 1;
            $rm->save();
            
            $this->fail('Seharusnya gagal karena idpet null');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-RM-002 | Data Validation | Gagal jika idpet tidak valid (FK Check) |
     */
    public function test_UT_V_RM_002_admin_harus_gagal_jika_idpet_tidak_valid()
    {
        try {
            RekamMedis::forceCreate([
                'anamnesa' => 'Test',
                'diagnosa' => 'Test',
                'idpet' => 999, // ID tidak ada di tabel pet
                'dokter_pemeriksa' => 1,
                'idreservasi_dokter' => 1,
                'created_at' => now()
            ]);
            $this->fail('Seharusnya gagal karena Foreign Key constraint');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-RM-003 | Data Validation | Gagal jika dokter pemeriksa tidak valid (FK Check) |
     */
    public function test_UT_V_RM_003_admin_harus_gagal_jika_dokter_tidak_valid()
    {
        try {
            RekamMedis::forceCreate([
                'anamnesa' => 'Test',
                'diagnosa' => 'Test',
                'idpet' => 1,
                'dokter_pemeriksa' => 888, // ID tidak ada di tabel role_user
                'idreservasi_dokter' => 1,
                'created_at' => now()
            ]);
            $this->fail('Seharusnya gagal karena Foreign Key constraint');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-RM-004 | Data Validation | Sukses simpan teks panjang |
     */
    public function test_UT_V_RM_004_admin_harus_sukses_simpan_teks_panjang()
    {
        $longText = str_repeat("Teks Rekam Medis Sangat Panjang ", 20);
        $rm = RekamMedis::forceCreate([
            'anamnesa' => $longText,
            'temuan_klinis' => 'Normal',
            'diagnosa' => 'Sehat',
            'idpet' => 1,
            'dokter_pemeriksa' => 1,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $this->assertEquals($longText, $rm->fresh()->anamnesa);
    }
}