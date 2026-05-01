<?php

namespace Tests\Integration;

use App\Models\Pet;

/**
 * SKENARIO INTEGRASI: User dengan banyak hewan peliharaan (IT-RM-DRM-004)
 */
class MultiplePetsTest extends IntegrationTestBase
{
    /**
     * Menggunakan setup tambahan khusus untuk skenario banyak hewan.
     */
    public function test_rekam_medis_tidak_tertukar_saat_pemilik_punya_banyak_pet()
    {
        // 1. Setup: Tambahkan pet kedua untuk pemilik yang sama (Ali)
        $petBobby = Pet::create([
            'nama' => 'Bobby',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => $this->pet->idras_hewan,
            'jenis_kelamin' => 'L'
        ]);

        // 2. Setup: Buat janji temu khusus untuk Bobby
        $appointmentBobby = $this->createActiveAppointment();
        $appointmentBobby->update(['idpet' => $petBobby->idpet]);

        // 3. Action: Admin input Rekam Medis untuk Bobby
        $response = $this->actingAs($this->admin)
            ->withSession(['user_role' => 1])
            ->post(route('admin.rekam-medis.store'), [
                'idreservasi_dokter' => $appointmentBobby->idreservasi_dokter,
                'idpet' => $petBobby->idpet, // Pilih Bobby
                'anamnesa' => 'Pemeriksaan rutin Bobby.',
                'temuan_klinis' => 'Kondisi baik.',
                'diagnosa' => 'Sehat.',
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        // 4. Assert: Pastikan data masuk ke Bobby, bukan Whiskers
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('rekam_medis', [
            'idpet' => $petBobby->idpet,
            'idreservasi_dokter' => $appointmentBobby->idreservasi_dokter
        ]);
    }
}