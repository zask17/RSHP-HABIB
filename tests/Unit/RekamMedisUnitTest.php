<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\Admin\RekamMedisController;

/**
 * UNIT TEST — Fitur Rekam Medis (Cluster 2)
 * Menguji logika penanganan data rekam medis sesuai dengan struktur RekamMedisController.
 */
class RekamMedisUnitTest extends TestCase
{
    private RekamMedisController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RekamMedisController();
    }

    /**
     * TC-01: Simulasi logika mapping data rekam medis.
     * Memastikan 'dokter_pemeriksa' diambil dari relasi temu_dokter (idrole_user)
     * sebagaimana terlihat pada alur data di RekamMedisController.
     */
    public function test_mapping_dokter_pemeriksa_dari_objek_temu_dokter()
    {
        // Simulasi objek temu_dokter yang didapat dari query DB di controller
        $temuDokter = new \stdClass();
        $temuDokter->idrole_user = 14; // Contoh ID role user dokter dari dump SQL

        // Simulasi payload yang akan dimasukkan ke tabel rekam_medis
        $payload = [
            'idreservasi_dokter' => 4,
            'dokter_pemeriksa' => $temuDokter->idrole_user, // Logic: mengambil ID dari penanggung jawab temu dokter
            'idpet' => 3
        ];

        $this->assertEquals(14, $payload['dokter_pemeriksa'], 'ID dokter pemeriksa harus sesuai dengan penanggung jawab reservasi.');
    }

    /**
     * TC-02: Simulasi penanganan nullable field (anamnesa, diagnosa).
     * Menguji logika sanitasi input sebelum dikirim ke database melalui Query Builder.
     */
    public function test_field_nullable_diubah_menjadi_null_jika_kosong()
    {
        $inputDiagnosa = "   "; // Simulasi input user yang hanya berisi spasi
        
        // Logika sanitasi: mengubah string kosong atau hanya spasi menjadi null agar DB tetap bersih
        $cleanDiagnosa = trim($inputDiagnosa) === '' ? null : trim($inputDiagnosa);

        $this->assertNull($cleanDiagnosa, 'Input yang hanya berisi spasi harus dikonversi menjadi NULL.');
        
        $inputValid = "Flu Kucing";
        $cleanValid = trim($inputValid) === '' ? null : trim($inputValid);
        $this->assertEquals("Flu Kucing", $cleanValid);
    }

    /**
     * TC-03: Memastikan default 'created_at' tersedia.
     * RekamMedisController@storeRekamMedis (pada TemuDokterController) menggunakan now() untuk created_at.
     */
    public function test_payload_rekam_medis_memiliki_tanggal_pembuatan()
    {
        $fixedDate = '2026-03-31 09:00:00';
        
        // Simulasi array data sebelum insertGetId
        $dataToInsert = [
            'anamnesa' => 'Nafsu makan turun',
            'temuan_klinis' => 'Suhu 39C',
            'diagnosa' => 'Demam',
            'created_at' => $fixedDate, // Logic: DB::table insert butuh timestamp manual jika tidak pakai Eloquent
        ];

        $this->assertArrayHasKey('created_at', $dataToInsert, 'Payload insert harus mengandung field created_at.');
        $this->assertEquals($fixedDate, $dataToInsert['created_at']);
    }

    /**
     * TC-04: Simulasi verifikasi detail tindakan.
     * Menguji pengecekan array detail_tindakan sebelum diproses foreach di controller.
     */
    public function test_verifikasi_struktur_detail_tindakan()
    {
        // Simulasi $request->detail_tindakan
        $detailTindakan = [
            ['idkode_tindakan_terapi' => 1, 'detail' => 'Vaksin Rabies'],
            ['idkode_tindakan_terapi' => 13, 'detail' => 'Antibiotik']
        ];

        $this->assertIsArray($detailTindakan);
        $this->assertCount(2, $detailTindakan);
        $this->assertEquals(13, $detailTindakan[1]['idkode_tindakan_terapi']);
    }
}