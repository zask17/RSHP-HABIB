<?php

namespace Tests\Integration;

use App\Models\TemuDokter;
use App\Models\Pet;

/**
 * INTEGRATION TEST: TEMU DOKTER & MULTIPLE PETS (PDF Skenario IT-RM-DRM-003 s/d 005)
 */
class AppointmentAndMedicalRecordIntegrationTest extends IntegrationTestBase
{
    /**
     * TEST: Create Temu Dokter Single Pet (Gambar 3 di PDF)
     */
    public function test_proses_create_temu_dokter_dengan_single_pet()
    {
        $response = $this->actingAs($this->resepsionis)
            ->post(route('resepsionis.temu-dokter.store'), [
                'idpet' => $this->pet->idpet,
                'idrole_user' => $this->idRoleUserDokter,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('temu_dokter', [
            'idpet' => $this->pet->idpet,
            'status' => 'M'
        ]);
    }

    /**
     * TEST: Multiple Pets dari User yang sama (Gambar 4 di PDF)
     */
    public function test_input_rekam_medis_multiple_pets()
    {
        // Buat pet kedua untuk pemilik yang sama
        $petKedua = Pet::create([
            'nama' => 'Bobby',
            'tanggal_lahir' => '2021-05-05',
            'warna_tanda' => 'Hitam',
            'jenis_kelamin' => 'L',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => $this->pet->idras_hewan
        ]);

        $aptPet1 = $this->createPendingAppointment($this->pet);
        $aptPet2 = $this->createPendingAppointment($petKedua);

        // Input Rekam Medis untuk Pet KEDUA (Bobby)
        $payload = [
            'idreservasi_dokter' => $aptPet2->idreservasi_dokter,
            'anamnesa' => "Nafsu makan menurun 2 hari.",
            'temuan_klinis' => 'Suhu meningkat ringan.',
            'diagnosa' => 'Infeksi saluran pencernaan ringan.'
        ];

        $response = $this->actingAs($this->admin)
            ->withSession(['user_role' => 1])
            ->post(route('admin.rekam-medis.store'), $payload);

        $response->assertSessionHas('success');
        
        // Verifikasi database: Rekam medis harus milik Bobby, bukan Pet pertama
        $this->assertDatabaseHas('rekam_medis', [
            'idreservasi_dokter' => $aptPet2->idreservasi_dokter,
            'diagnosa' => 'Infeksi saluran pencernaan ringan.'
        ]);
    }

    /**
     * TEST: Pet dihapus/nonaktif sebelum submit rekam medis (Gambar 5 di PDF)
     */
    public function test_input_rekam_medis_saat_pet_dihapus_sebelum_submit()
    {
        $temuDokter = $this->createPendingAppointment();

        // Simulasi Pet dihapus secara halus (Soft Delete) sebelum form dikirim
        $this->pet->delete();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.rekam-medis.store'), [
                'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
                'anamnesa' => 'Anamnesa test',
                'temuan_klinis' => 'Temuan valid',
                'diagnosa' => 'Diagnosa asal'
            ]);

        // Harapannya gagal (422) karena Pet sudah tidak aktif
        $response->assertStatus(422);
    }
}