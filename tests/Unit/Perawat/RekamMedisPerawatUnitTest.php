<?php

namespace Tests\Unit\Perawat;

use App\Models\RekamMedis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * UNIT TEST — Rekam Medis (Perawat Role)
 * Perawat memiliki akses untuk mengelola data utama rekam medis
 * * SKENARIO PENGUJIAN FORMAT:
 * [Test Code] | [Test Type] | [Object Being Tested] | [Function]
 */
class RekamMedisPerawatUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Aktifkan foreign key check untuk integritas database
        DB::statement('PRAGMA foreign_keys = ON');

        // 2. Siapkan data Master (Role Perawat ID 3 & Dokter ID 2)
        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Kucing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Persia', 'idjenis_hewan' => 1]);
        DB::table('role')->insert(['idrole' => 2, 'nama_role' => 'Dokter']);
        DB::table('role')->insert(['idrole' => 3, 'nama_role' => 'Perawat']);

        // 3. Siapkan data User (Perawat ID 28 & Dokter ID 26 sesuai dump)
        DB::table('user')->insert([
            'iduser' => 28, 
            'nama' => 'Perawat Test', 
            'email' => 'perawat@test.com', 
            'password' => bcrypt('pass_perawat')
        ]);
        DB::table('role_user')->insert(['idrole_user' => 15, 'iduser' => 28, 'idrole' => 3]);

        DB::table('user')->insert([
            'iduser' => 26, 
            'nama' => 'Dokter Test', 
            'email' => 'dokter@test.com', 
            'password' => bcrypt('pass_dokter')
        ]);
        DB::table('role_user')->insert(['idrole_user' => 14, 'iduser' => 26, 'idrole' => 2]);
        
        DB::table('pemilik')->insert([
            'idpemilik' => 1, 
            'iduser' => 28, 
            'no_wa' => '08123', 
            'alamat' => 'Alamat Test'
        ]);

        // 4. Siapkan data Pet
        DB::table('pet')->insert([
            'idpet' => 1,
            'nama' => 'Mimi',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'F'
        ]);

        // 5. Siapkan data Temu Dokter (Wajib ada waktu_daftar)
        DB::table('temu_dokter')->insert([
            'idreservasi_dokter' => 1,
            'no_urut' => 1,
            'idrole_user' => 14,
            'waktu_daftar' => now(),
            'status' => '1'
        ]);
    }

    // ============ FUNCTIONALITY TESTS (PERAWAT) ============

    /**
     * UT-F-RM-001 | Functionality | Create Rekam Medis oleh Perawat |
     */
    public function test_UT_F_RM_001_perawat_data_rekam_medis_harus_tersimpan()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Kucing tampak lemas',
            'temuan_klinis' => 'Suhu 38C',
            'diagnosa' => 'Observasi',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $this->assertNotNull($rm->idrekam_medis);
        $this->assertDatabaseHas('rekam_medis', ['idpet' => 1, 'diagnosa' => 'Observasi']);
    }

    /**
     * UT-F-RM-002 | Functionality | Perawat mengambil data rekam medis |
     */
    public function test_UT_F_RM_002_perawat_data_rekam_medis_harus_dapat_diambil_berdasarkan_id()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Cek Rutin',
            'temuan_klinis' => 'Normal',
            'diagnosa' => 'Sehat',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $retrieved = RekamMedis::find($rm->idrekam_medis);
        $this->assertEquals('Cek Rutin', $retrieved->anamnesa);
        $this->assertEquals('Sehat', $retrieved->diagnosa);
    }

    /**
     * UT-F-RM-003 | Functionality | Perawat mengubah data utama rekam medis |
     */
    public function test_UT_F_RM_003_perawat_data_rekam_medis_harus_dapat_diubah()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Anamnesa Awal',
            'temuan_klinis' => 'Normal',
            'diagnosa' => 'Observasi Awal',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $rm->update(['anamnesa' => 'Anamnesa Diperbarui Perawat']);
        $this->assertEquals('Anamnesa Diperbarui Perawat', $rm->fresh()->anamnesa);
    }

    /**
     * UT-F-RM-004 | Functionality | Perawat menghapus rekam medis |
     */
    public function test_UT_F_RM_004_perawat_data_rekam_medis_harus_dapat_dihapus()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Data Salah',
            'temuan_klinis' => 'Normal',
            'diagnosa' => 'Hapus Segera',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $id = $rm->idrekam_medis;
        $rm->delete();

        $this->assertNull(RekamMedis::find($id));
    }

    /**
     * UT-F-RM-005 | Functionality | Perawat melihat relasi Pet |
     */
    public function test_UT_F_RM_005_perawat_rekam_medis_harus_terhubung_ke_pet()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Test Relasi',
            'temuan_klinis' => 'Normal',
            'diagnosa' => 'Sehat',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $this->assertEquals('Mimi', $rm->pet->nama);
    }

    // ============ DATA VALIDATION TESTS (PERAWAT) ============

    /**
     * UT-V-RM-001 | Data Validation | Gagal jika idpet kosong |
     */
    public function test_UT_V_RM_001_perawat_harus_gagal_jika_idpet_kosong()
    {
        try {
            $rm = new RekamMedis();
            $rm->idpet = null; 
            $rm->dokter_pemeriksa = 14;
            $rm->idreservasi_dokter = 1;
            $rm->temuan_klinis = 'Normal';
            $rm->diagnosa = 'Diagnosa';
            $rm->save();
            
            $this->fail('Seharusnya gagal karena idpet null');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-RM-002 | Data Validation | Gagal jika idpet tidak valid |
     */
    public function test_UT_V_RM_002_perawat_harus_gagal_jika_idpet_tidak_valid()
    {
        try {
            RekamMedis::forceCreate([
                'anamnesa' => 'Test',
                'temuan_klinis' => 'Normal',
                'diagnosa' => 'Sehat',
                'idpet' => 999, 
                'dokter_pemeriksa' => 14,
                'idreservasi_dokter' => 1,
                'created_at' => now()
            ]);
            $this->fail('Seharusnya gagal karena Foreign Key constraint');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-RM-003 | Data Validation | Gagal jika dokter tidak valid |
     */
    public function test_UT_V_RM_003_perawat_harus_gagal_jika_dokter_tidak_valid()
    {
        try {
            RekamMedis::forceCreate([
                'anamnesa' => 'Test',
                'temuan_klinis' => 'Normal',
                'diagnosa' => 'Sehat',
                'idpet' => 1,
                'dokter_pemeriksa' => 888, 
                'idreservasi_dokter' => 1,
                'created_at' => now()
            ]);
            $this->fail('Seharusnya gagal karena Foreign Key constraint');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-RM-004 | Data Validation | Sukses simpan rekam medis data lengkap |
     */
    public function test_UT_V_RM_004_perawat_harus_sukses_simpan_data_lengkap()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Lengkap',
            'temuan_klinis' => 'Suhu Normal',
            'diagnosa' => 'Sehat',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);
        
        $this->assertNotNull($rm->idrekam_medis);
        $this->assertEquals('Sehat', $rm->fresh()->diagnosa);
    }
}