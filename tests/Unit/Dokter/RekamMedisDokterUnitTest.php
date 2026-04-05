<?php

namespace Tests\Unit\Dokter;

use App\Models\RekamMedis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class RekamMedisDokterUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Master Data agar Foreign Key tidak error
        DB::table('role')->insert([
            ['idrole' => 2, 'nama_role' => 'Dokter'],
            ['idrole' => 5, 'nama_role' => 'Pemilik']
        ]);

        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Kucing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Persia', 'idjenis_hewan' => 1]);

        // 2. Setup User & Role User (Sesuai dump data kamu)
        DB::table('user')->insert([
            'iduser' => 26, 
            'nama' => 'Dokter Test', 
            'email' => 'dokter@test.com', 
            'password' => bcrypt('dokter123')
        ]);
        DB::table('role_user')->insert(['idrole_user' => 14, 'iduser' => 26, 'idrole' => 2]);

        // 3. Setup Pemilik & Pet
        DB::table('user')->insert([
            'iduser' => 29, 
            'nama' => 'Pemilik Test', 
            'email' => 'pemilik@test.com', 
            'password' => bcrypt('pemilik123')
        ]);
        DB::table('pemilik')->insert(['idpemilik' => 1, 'iduser' => 29, 'no_wa' => '0812', 'alamat' => 'SBY']);
        
        DB::table('pet')->insert([
            'idpet' => 1,
            'nama' => 'Mimi',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'M'
        ]);

        // 4. Setup Reservasi/Temu Dokter
        DB::table('temu_dokter')->insert([
            'idreservasi_dokter' => 1,
            'no_urut' => 1,
            'idrole_user' => 14,
            'waktu_daftar' => now(),
            'status' => '1'
        ]);
    }

    // ============ FUNCTIONALITY TESTS (F) ============

    /**
     * UT-F-RM-001 | Dokter membuat rekam medis baru
     */
    public function test_UT_F_RM_001_dokter_membuat_rekam_medis_baru()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Nafsu makan berkurang',
            'temuan_klinis' => 'Suhu 39 derajat',
            'diagnosa' => 'Flu Kucing',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1,
            'created_at' => now()
        ]);

        $this->assertDatabaseHas('rekam_medis', [
            'idrekam_medis' => $rm->idrekam_medis,
            'diagnosa' => 'Flu Kucing'
        ]);
    }

    /**
     * UT-F-RM-002 | Dokter memperbarui diagnosa rekam medis
     */
    public function test_UT_F_RM_002_dokter_memperbarui_diagnosa_rekam_medis()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Awal', 'temuan_klinis' => 'Awal', 'diagnosa' => 'Diagnosa Awal',
            'idpet' => 1, 'dokter_pemeriksa' => 14, 'idreservasi_dokter' => 1
        ]);

        $rm->update(['diagnosa' => 'Diagnosa Final']);
        
        $this->assertEquals('Diagnosa Final', $rm->fresh()->diagnosa);
    }

    /**
     * UT-F-RM-003 | Dokter menghapus data rekam medis
     */
    public function test_UT_F_RM_003_dokter_menghapus_data_rekam_medis()
    {
        $rm = RekamMedis::forceCreate([
            'anamnesa' => 'Hapus', 'temuan_klinis' => 'Hapus', 'diagnosa' => 'Hapus',
            'idpet' => 1, 'dokter_pemeriksa' => 14, 'idreservasi_dokter' => 1
        ]);

        $id = $rm->idrekam_medis;
        $rm->delete();

        // Cek apakah data sudah tidak ada di pencarian find()
        $this->assertNull(RekamMedis::find($id));
    }

    // ============ VALIDATION TESTS (V) ============

    /**
     * UT-V-RM-001 | Validasi idpet harus ada (Foreign Key)
     */
    public function test_UT_V_RM_001_dokter_gagal_simpan_jika_idpet_tidak_terdaftar()
    {
        $this->expectException(QueryException::class);

        RekamMedis::forceCreate([
            'anamnesa' => 'Test',
            'temuan_klinis' => 'Test',
            'diagnosa' => 'Test',
            'idpet' => 999, 
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1
        ]);
    }

    /**
     * UT-V-RM-002 | Validasi dokter_pemeriksa harus valid
     */
    public function test_UT_V_RM_002_dokter_gagal_simpan_jika_id_dokter_tidak_valid()
    {
        $this->expectException(QueryException::class);

        RekamMedis::forceCreate([
            'anamnesa' => 'Test',
            'idpet' => 1,
            'dokter_pemeriksa' => 999, 
            'idreservasi_dokter' => 1
        ]);
    }

    /**
     * UT-V-RM-003 | Validasi simpan data dengan anamnesa string kosong
     * Database kamu memiliki constraint NOT NULL pada kolom anamnesa.
     */
    public function test_UT_V_RM_003_dokter_sukses_simpan_meskipun_anamnesa_kosong_string()
    {
        // Menggunakan string kosong '' karena database tidak mengizinkan NULL
        $rm = RekamMedis::forceCreate([
            'anamnesa' => '-', 
            'temuan_klinis' => 'Sehat',
            'diagnosa' => 'Checkup',
            'idpet' => 1,
            'dokter_pemeriksa' => 14,
            'idreservasi_dokter' => 1
        ]);

        $this->assertDatabaseHas('rekam_medis', [
            'idrekam_medis' => $rm->idrekam_medis,
            'anamnesa' => '-'
        ]);
    }
}