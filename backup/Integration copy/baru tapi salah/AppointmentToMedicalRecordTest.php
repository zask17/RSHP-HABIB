<?php

namespace Tests\Integration;

use App\Models\TemuDokter;
use App\Models\RekamMedis;
use App\Models\KodeTindakanTerapi;

/**
 * LEVEL 1 - END-TO-END INTEGRATION TEST
 * Skenario: Resepsionis daftar -> Dokter periksa -> Perawat verifikasi.
 */
class AppointmentToMedicalRecordTest extends IntegrationTestBase
{
    /**
     * @test
     * Skenario IT-TM-RM-01: Alur penuh dari pendaftaran hingga detail tindakan.
     */
    public function test_alur_penuh_pendaftaran_sampai_detail_tindakan()
    {
        // ─────────────────────────────────────────────────────────────
        // STAGE 1: Resepsionis membuat janji temu
        // ─────────────────────────────────────────────────────────────
        $responseApt = $this->actingAs($this->resepsionis)
            ->withSession(['user_role' => 4])
            ->post('/resepsionis/temu-dokter', [
                'idpet' => $this->pet->idpet,
                'idrole_user' => $this->idRoleUserDokter
            ]);

        $responseApt->assertStatus(302); // Redirect setelah simpan
        $temuDokter = TemuDokter::latest('idreservasi_dokter')->first();
        $this->assertNotNull($temuDokter);

        // ─────────────────────────────────────────────────────────────
        // STAGE 2: Perawat input Rekam Medis (IT-TM-RM-01 Step 1)
        // ─────────────────────────────────────────────────────────────
        $responseRM = $this->actingAs($this->perawat)
            ->withSession(['user_role' => 3])
            ->post('/perawat/rekam-medis', [
                'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
                'idpet' => $this->pet->idpet,
                'anamnesa' => 'Anjing demam tinggi selama 3 hari.',
                'temuan_klinis' => 'Suhu tubuh 39.5 C, dehidrasi ringan.',
                'diagnosa' => 'Suspek Infeksi Viral',
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        $responseRM->assertSessionHas('success');
        $rekamMedis = RekamMedis::latest('idrekam_medis')->first();

        // ─────────────────────────────────────────────────────────────
        // STAGE 3: Dokter input Tindakan Terapi (IT-TM-RM-01 Step 2)
        // ─────────────────────────────────────────────────────────────
        // Siapkan master kode tindakan
        $kodeTindakan = KodeTindakanTerapi::firstOrCreate(
            ['idkode_tindakan_terapi' => 1],
            ['kode' => 'T01', 'deskripsi_tindakan_terapi' => 'Vaksinasi Rabies', 'idkategori' => 1, 'idkategori_klinis' => 1]
        );

        $responseDetail = $this->actingAs($this->dokter)
            ->withSession(['user_role' => 2])
            ->post("/dokter/rekam-medis/{$rekamMedis->idrekam_medis}/tindakan", [
                'idkode_tindakan_terapi' => $kodeTindakan->idkode_tindakan_terapi,
                'detail' => 'Vaksinasi diberikan, kondisi pasca-tindakan stabil.'
            ]);

        $responseDetail->assertStatus(302);
        
        // Verifikasi Akhir di Database
        $this->assertDatabaseHas('detail_rekam_medis', [
            'idrekam_medis' => $rekamMedis->idrekam_medis,
            'detail' => 'Vaksinasi diberikan, kondisi pasca-tindakan stabil.'
        ]);
    }

    /**
     * @test
     * Skenario Negatif (IT-TM-RM-02): Input field kosong.
     */
    public function test_input_rekam_medis_field_kosong_harus_ditolak_validasi()
    {
        $temuDokter = $this->createAppointment();

        $response = $this->actingAs($this->perawat)
            ->withSession(['user_role' => 3])
            ->post('/perawat/rekam-medis', [
                'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
                'idpet' => $this->pet->idpet,
                'anamnesa' => '', // Kosong
                'temuan_klinis' => '',
                'diagnosa' => ''
            ]);

        // Berdasarkan regulasi di PDF, ini harus memicu error session
        $response->assertSessionHasErrors(['anamnesa', 'temuan_klinis', 'diagnosa']);
    }
}