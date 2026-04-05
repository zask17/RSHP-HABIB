<?php

namespace Tests\Unit\Resepsionis;

use App\Models\RekamMedis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * UNIT TEST — Rekam Medis (Resepsionis Role)
 * Resepsionis memiliki akses terbatas untuk melihat dan mendaftarkan data awal rekam medis
 * * SKENARIO PENGUJIAN FORMAT:
 * [Test Code] | [Test Type] | [Object Being Tested] | [Function]
 */
class RekamMedisResepsionisUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Aktifkan foreign key check
        DB::statement('PRAGMA foreign_keys = ON');

        // 2. Siapkan data Master
        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Anjing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Bulldog', 'idjenis_hewan' => 1]);
        DB::table('role')->insert(['idrole' => 2, 'nama_role' => 'Dokter']);
        DB::table('role')->insert(['idrole' => 4, 'nama_role' => 'Resepsionis']);

        // 3. Siapkan data User & Role User
        DB::table('user')->insert([
            'iduser' => 27, 
            'nama' => 'Resepsionis User', 
            'email' => 'resepsionis@mail.com', 
            'password' => bcrypt('pass123')
        ]);
        DB::table('role_user')->insert(['idrole_user' => 19, 'iduser' => 27, 'idrole' => 4]);

        DB::table('user')->insert([
            'iduser' => 26, 
            'nama' => 'Dokter User', 
            'email' => 'dokter@mail.com', 
            'password' => bcrypt('pass123')
        ]);
        DB::table('role_user')->insert(['idrole_user' => 14, 'iduser' => 26, 'idrole' => 2]);
        
        DB::table('pemilik')->insert([
            'idpemilik' => 1, 
            'iduser' => 27, 
            'no_wa' => '0812345', 
            'alamat' => 'Alamat'
        ]);

        // 4. Siapkan data Pet
        DB::table('pet')->insert([
            'idpet' => 1,
            'nama' => 'Buddy',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'M'
        ]);

        // 5. Siapkan data Temu Dokter
        DB::table('temu_dokter')->insert([
            'idreservasi_dokter' => 1,
            'no_urut' => 1,
            'idrole_user' => 14,
            'waktu_daftar' => now(),
            'status' => '1'
        ]);
    }

    // ============ FUNCTIONALITY TESTS (RESEPSIONIS) ============

    /**
     * UT-F-RM-001 | Functionality | Create Rekam Medis oleh Resepsionis |
     */
    public function test_UT_F_RM_001_resepsionis_data_rekam_medis_harus_tersimpan()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Anjing lemas',
            'temuan_klinis' => 'Normal', // KOREKSI: Tambahkan ini
            'diagnosa' => 'Belum diperiksa',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $this->assertNotNull($rm->idrekam_medis);
        $this->assertDatabaseHas('rekam_medis', ['idpet' => 1]);
    }

    /**
     * UT-F-RM-002 | Functionality | Resepsionis dapat mengambil data rekam medis |
     */
    public function test_UT_F_RM_002_resepsionis_data_rekam_medis_harus_dapat_diambil_berdasarkan_id()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Cek Rutin',
            'temuan_klinis' => 'Normal', // KOREKSI: Tambahkan ini
            'diagnosa' => 'Sehat',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $retrieved = RekamMedis::find($rm->idrekam_medis);
        $this->assertEquals('Sehat', $retrieved->diagnosa);
    }

    /**
     * UT-F-RM-003 | Functionality | Resepsionis dapat mengubah data awal rekam medis |
     */
    public function test_UT_F_RM_003_resepsionis_data_rekam_medis_harus_dapat_diubah()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Lama',
            'temuan_klinis' => 'Normal', // KOREKSI: Tambahkan ini
            'diagnosa' => 'Lama',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $rm->update(['anamnesa' => 'Anamnesa Baru']);
        $this->assertEquals('Anamnesa Baru', $rm->fresh()->anamnesa);
    }

    /**
     * UT-F-RM-004 | Functionality | Resepsionis dapat menghapus rekam medis |
     */
    public function test_UT_F_RM_004_resepsionis_data_rekam_medis_harus_dapat_dihapus()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Hapus',
            'temuan_klinis' => 'Normal', // KOREKSI: Tambahkan ini
            'diagnosa' => 'Hapus',
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
     * UT-F-RM-005 | Functionality | Relasi ke Pet dapat terbaca oleh Resepsionis |
     */
    public function test_UT_F_RM_005_resepsionis_rekam_medis_harus_terhubung_ke_pet()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Cek Relasi',
            'temuan_klinis' => 'Normal', // KOREKSI: Tambahkan ini
            'diagnosa' => 'Sehat',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $this->assertEquals('Buddy', $rm->pet->nama);
    }

    // ============ DATA VALIDATION TESTS (RESEPSIONIS) ============

    public function test_UT_V_RM_001_resepsionis_harus_gagal_jika_idpet_kosong()
    {
        try {
            $rm = new RekamMedis();
            $rm->idpet = null; 
            $rm->dokter_pemeriksa = 14;
            $rm->idreservasi_dokter = 1;
            $rm->temuan_klinis = 'Normal';
            $rm->save();
            $this->fail('Seharusnya gagal');
        } catch (\Exception $e) { $this->assertTrue(true); }
    }

    public function test_UT_V_RM_002_resepsionis_harus_gagal_jika_idpet_tidak_valid()
    {
        try {
            RekamMedis::forceCreate([
                'anamnesa' => 'Test',
                'temuan_klinis' => 'Normal',
                'idpet' => 999, 
                'dokter_pemeriksa' => 14,
                'idreservasi_dokter' => 1,
                'created_at' => now()
            ]);
            $this->fail('Seharusnya gagal');
        } catch (QueryException $e) { $this->assertTrue(true); }
    }

    public function test_UT_V_RM_003_resepsionis_harus_gagal_jika_dokter_tidak_valid()
    {
        try {
            RekamMedis::forceCreate([
                'anamnesa' => 'Test',
                'temuan_klinis' => 'Normal',
                'idpet' => 1,
                'dokter_pemeriksa' => 888, 
                'idreservasi_dokter' => 1,
                'created_at' => now()
            ]);
            $this->fail('Seharusnya gagal');
        } catch (QueryException $e) { $this->assertTrue(true); }
    }

    public function test_UT_V_RM_004_resepsionis_harus_sukses_simpan_data_lengkap()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Lengkap',
            'temuan_klinis' => 'Normal',
            'diagnosa' => 'Sehat',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);
        $this->assertNotNull($rm->idrekam_medis);
    }
}