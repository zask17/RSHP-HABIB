<?php

namespace Tests\Integration;

use App\Models\RekamMedis;
use App\Models\TemuDokter;
use App\Models\Pet;
use App\Models\KodeTindakanTerapi;
use Illuminate\Support\Facades\DB;

/**
 * INTEGRATION TEST: ALUR REKAM MEDIS (PDF Skenario IT-TM-RM-01 & 02)
 */
class IntegrationTestRekamMedisFlow extends IntegrationTestBase
{
    /**
     * TEST POSITIF: Alur Create Rekam Medis Sampai Detail Rekam Medis (Gambar 1 di PDF)
     * Skenario: Input temuan klinis < 1000 karakter.
     */
    public function test_proses_rekam_medis_sampai_detail_berhasil()
    {
        // 1. Persiapan Data (Temu Dokter)
        $temuDokter = $this->createPendingAppointment();
        $kodeTindakan = KodeTindakanTerapi::firstOrCreate(['idkode_tindakan_terapi' => 1], ['nama_tindakan' => 'Vaksinasi']);

        $temuanKlinis = "Suhu tubuh 39.5 C, dehidrasi ringan, pernapasan cepat. Kondisi stabil.";

        // 2. Perawat mengisi Rekam Medis awal
        $response1 = $this->actingAs($this->perawat)
            ->withSession(['user_role' => 3])
            ->post('/perawat/rekam-medis', [
                'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
                'anamnesa' => 'Anjing demam tinggi selama 3 hari',
                'temuan_klinis' => $temuanKlinis,
                'diagnosa' => 'Suspek Infeksi Viral'
            ]);

        $response1->assertStatus(302);
        $response1->assertSessionHas('success', 'Rekam medis berhasil ditambahkan!');

        // Verifikasi data masuk ke DB
        $rekamMedis = RekamMedis::latest('idrekam_medis')->first();
        $this->assertNotNull($rekamMedis);
        $this->assertEquals($temuanKlinis, $rekamMedis->temuan_klinis);

        // 3. Dokter mengisi Tindakan (Detail Rekam Medis)
        $response2 = $this->actingAs($this->dokter)
            ->withSession(['user_role' => 2])
            ->post("/dokter/rekam-medis/{$rekamMedis->idrekam_medis}/tindakan", [
                'idkode_tindakan_terapi' => $kodeTindakan->idkode_tindakan_terapi,
                'detail' => 'Vaksinasi rabies diberikan, hewan stabil.'
            ]);

        $response2->assertStatus(302);
        $this->assertDatabaseHas('detail_rekam_medis', [
            'idrekam_medis' => $rekamMedis->idrekam_medis,
            'detail' => 'Vaksinasi rabies diberikan, hewan stabil.'
        ]);
    }

    /**
     * TEST NEGATIF: Input Field Kosong (PDF Skenario IT-TM-RM-02)
     * Skenario: Memastikan validasi menolak field anamnesa/diagnosa yang kosong.
     */
    public function test_alur_input_field_kosong_harus_gagal()
    {
        $temuDokter = $this->createPendingAppointment();

        // Mencoba input data kosong
        $response = $this->actingAs($this->perawat)
            ->withSession(['user_role' => 3])
            ->post('/perawat/rekam-medis', [
                'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
                'anamnesa' => null,
                'temuan_klinis' => null,
                'diagnosa' => null
            ]);

        // Berdasarkan rekomendasi PDF, ini harus error (422 atau redirect dengan errors)
        $response->assertSessionHasErrors(['anamnesa', 'temuan_klinis', 'diagnosa']);
    }
}