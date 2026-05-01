<?php

namespace Tests\Integration;

use App\Models\TemuDokter;
use App\Models\RekamMedis;
use App\Models\KodeTindakanTerapi;
use App\Models\Kategori;
use App\Models\KategoriKlinis;

class RekamMedisFlowTest extends IntegrationTestBase
{
    /**
     * IT-TM-RM-01: Positif - Input Rekam Medis sampai Detail Rekam Medis
     * Skenario: Perawat input RM, Dokter input Detail/Tindakan.
     */
    public function test_alur_input_rekam_medis_sampai_detail_berhasil()
    {
        // 1. Buat Janji Temu (Temu Dokter)
        $temuDokter = TemuDokter::create([
            'idpet' => $this->pet->idpet,
            'idrole_user' => $this->idRoleUserDokter,
            'waktu_daftar' => now(),
            'status' => '0' // Menunggu
        ]);

        // 2. Perawat Input Rekam Medis (IT-TM-RM-01 Step 1)
        $temuanKlinis = "Suhu tubuh 39.5°C, dehidrasi ringan.";
        
        $responseRM = $this->actingAs($this->perawat)
            ->withSession(['user_role' => 3])
            ->post('/perawat/rekam-medis', [
                'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
                'idpet' => $this->pet->idpet,
                'anamnesa' => 'Anjing demam tinggi dan lemas selama 3 hari.',
                'temuan_klinis' => $temuanKlinis,
                'diagnosa' => 'Suspek Infeksi Viral',
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        $responseRM->assertStatus(302);
        $this->assertDatabaseHas('rekam_medis', ['temuan_klinis' => $temuanKlinis]);
        
        $rekamMedis = RekamMedis::where('idreservasi_dokter', $temuDokter->idreservasi_dokter)->first();

        // 3. Dokter Input Detail Tindakan (IT-TM-RM-01 Step 2)
        $kat = Kategori::create(['nama_kategori' => 'Vaksinasi']);
        $katKlinis = KategoriKlinis::create(['nama_kategori_klinis' => 'Terapi']);
        $kodeTindakan = KodeTindakanTerapi::create([
            'kode' => 'T01',
            'nama_tindakan' => 'Vaksinasi Rabies',
            'idkategori' => $kat->idkategori,
            'idkategori_klinis' => $katKlinis->idkategori_klinis
        ]);

        $responseDetail = $this->actingAs($this->dokter)
            ->withSession(['user_role' => 2])
            ->post("/dokter/rekam-medis/{$rekamMedis->idrekam_medis}/tindakan", [
                'idkode_tindakan_terapi' => $kodeTindakan->idkode_tindakan_terapi,
                'detail' => 'Vaksinasi rabies diberikan, hewan stabil.'
            ]);

        $responseDetail->assertStatus(302);
        $this->assertDatabaseHas('detail_rekam_medis', [
            'idrekam_medis' => $rekamMedis->idrekam_medis,
            'detail' => 'Vaksinasi rabies diberikan, hewan stabil.'
        ]);
    }

    /**
     * IT-TM-RM-02: Negatif - Input Field Kosong
     * Skenario: Memastikan sistem menolak (Validation Error) jika field wajib kosong.
     */
    public function test_input_rekam_medis_field_kosong_harus_gagal()
    {
        $temuDokter = TemuDokter::create([
            'idpet' => $this->pet->idpet,
            'idrole_user' => $this->idRoleUserDokter,
            'waktu_daftar' => now(),
            'status' => '0'
        ]);

        $response = $this->actingAs($this->perawat)
            ->withSession(['user_role' => 3])
            ->post('/perawat/rekam-medis', [
                'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
                'idpet' => $this->pet->idpet,
                'anamnesa' => '', // Kosong
                'temuan_klinis' => '', // Kosong
                'diagnosa' => '', // Kosong
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        // Berdasarkan PDF, ini HARUS gagal validasi
        $response->assertSessionHasErrors(['anamnesa', 'temuan_klinis', 'diagnosa']);
    }
}