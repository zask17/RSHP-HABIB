<?php

namespace Tests\Integration;

use App\Models\TemuDokter;
use App\Models\RekamMedis;
use App\Models\KodeTindakanTerapi;

/**
 * LEVEL 1: PENGUJIAN ALUR UTAMA (CRUD Terintegrasi)
 * * Pengujian ini menggunakan pendekatan Top-Down dengan memanggil 
 * helper dari Base Class untuk mensimulasikan status sistem.
 */
class RekamMedisFlowIntegrationTest extends IntegrationTestBase
{
    /**
     * IT-TM-RM-01: Positif - Alur Input RM sampai Detail RM
     * * Menguji integrasi antara:
     * 1. Pendaftaran (TemuDokter)
     * 2. Input Rekam Medis (Store Perawat)
     * 3. Input Tindakan (Store Dokter)
     * * @test
     */
    public function test_alur_penuh_rekam_medis_sampai_detail_berhasil()
    {
        // STAGE 1: Gunakan helper untuk "Previous Step" (Top-Down)
        $janjiTemu = $this->createPendingAppointment();

        // STAGE 2: Simulasi Perawat Input Rekam Medis (Level 1 Method Utama)
        $temuanKlinis = "Suhu 39.5, dehidrasi ringan, membran mukosa pucat.";
        
        $responseRM = $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => 3])
            ->post('/perawat/rekam-medis', [
                'idreservasi_dokter' => $janjiTemu->idreservasi_dokter,
                'idpet' => $this->pet->idpet,
                'anamnesa' => 'Anjing lemas dan tidak nafsu makan selama 3 hari.',
                'temuan_klinis' => $temuanKlinis,
                'diagnosa' => 'Suspek Infeksi Viral',
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        // Verifikasi Respon Level 2 & 3 (Redirect & Session)
        $responseRM->assertStatus(302);
        $this->assertDatabaseHas('rekam_medis', ['temuan_klinis' => $temuanKlinis]);

        // Ambil hasil insert untuk tahap selanjutnya
        $rekamMedis = RekamMedis::latest('idrekam_medis')->first();

        // STAGE 3: Simulasi Dokter Input Tindakan (Level 1 Detail Spesifik)
        $kodeTindakan = KodeTindakanTerapi::first() ?? KodeTindakanTerapi::create([
            'idkode_tindakan_terapi' => 1, 'kode' => 'T01', 'idkategori' => 1, 'idkategori_klinis' => 1
        ]);

        $responseDetail = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => 2])
            ->post("/dokter/rekam-medis/{$rekamMedis->idrekam_medis}/tindakan", [
                'idkode_tindakan_terapi' => $kodeTindakan->idkode_tindakan_terapi,
                'detail' => 'Vaksinasi diberikan secara intramuskular.'
            ]);

        // Verifikasi Akhir
        $responseDetail->assertStatus(302);
        $this->assertDatabaseHas('detail_rekam_medis', [
            'idrekam_medis' => $rekamMedis->idrekam_medis,
            'detail' => 'Vaksinasi diberikan secara intramuskular.'
        ]);
    }

    /**
     * IT-TM-RM-02: Negatif - Input Field Kosong (Constraint Validation)
     * * @test
     */
    public function test_input_rekam_medis_kosong_harus_gagal_validasi()
    {
        $janjiTemu = $this->createPendingAppointment();

        $response = $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => 3])
            ->post('/perawat/rekam-medis', [
                'idreservasi_dokter' => $janjiTemu->idreservasi_dokter,
                'idpet' => $this->pet->idpet,
                'anamnesa' => '', // Kosong
                'temuan_klinis' => '', // Kosong
                'diagnosa' => '', // Kosong
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        // Verifikasi Level 3: Session error must exist
        $response->assertSessionHasErrors(['anamnesa', 'temuan_klinis', 'diagnosa']);
        $this->assertEquals(0, RekamMedis::count(), 'Rekam medis tidak boleh tersimpan jika data kosong');
    }

    /**
     * IT-RM-DRM-005: Negatif - Pet Dihapus Sebelum Submit (Integritas Data)
     * * @test
     */
    public function test_input_rekam_medis_gagal_jika_pet_dihapus_sebelum_submit()
    {
        $apt = $this->createPendingAppointment();

        // Simulasi Level 2: Pet dihapus/nonaktif oleh admin di tab lain
        $this->pet->delete();

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => 1])
            ->post('/admin/rekam-medis', [
                'idreservasi_dokter' => $apt->idreservasi_dokter,
                'idpet' => $this->pet->idpet,
                'anamnesa' => 'Test', 'temuan_klinis' => 'Test', 'diagnosa' => 'Test',
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        // Berdasarkan skenario PDF: Invalid pet lifecycle must be rejected (422)
        $response->assertStatus(422);
    }
}