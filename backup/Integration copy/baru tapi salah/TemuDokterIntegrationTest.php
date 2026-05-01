<?php

namespace Tests\Integration;

use App\Models\Pet;
use App\Models\TemuDokter;
use App\Models\RekamMedis;

class TemuDokterIntegrationTest extends IntegrationTestBase
{
    /**
     * IT-RM-DRM-003: Positif - Create Temu Dokter Single Pet (Gambar 3)
     */
    public function test_proses_create_temu_dokter_single_pet_berhasil()
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
            'idrole_user' => $this->idRoleUserDokter,
            'status' => '0'
        ]);
    }

    /**
     * IT-RM-DRM-004: Positif - Input RM dengan Multiple Pets (Gambar 4)
     */
    public function test_input_rekam_medis_dengan_multiple_pets_berhasil()
    {
        // Tambahkan pet kedua untuk pemilik yang sama
        $pet2 = Pet::create([
            'nama' => 'Bobby',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => $this->pet->idras_hewan,
            'jenis_kelamin' => 'L'
        ]);

        $aptPetBobby = TemuDokter::create([
            'idpet' => $pet2->idpet,
            'idrole_user' => $this->idRoleUserDokter,
            'waktu_daftar' => now(),
            'status' => '0'
        ]);

        // Input RM untuk Bobby
        $response = $this->actingAs($this->admin)
            ->withSession(['user_role' => 1])
            ->post(route('admin.rekam-medis.store'), [
                'idreservasi_dokter' => $aptPetBobby->idreservasi_dokter,
                'idpet' => $pet2->idpet,
                'anamnesa' => 'Nafsu makan menurun selama 2 hari.',
                'temuan_klinis' => 'Suhu tubuh meningkat ringan.',
                'diagnosa' => 'Infeksi saluran pencernaan ringan.',
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        $response->assertSessionHas('success');
        
        // Verifikasi RM tersimpan untuk Bobby, bukan Whiskers
        $this->assertDatabaseHas('rekam_medis', [
            'idpet' => $pet2->idpet,
            'diagnosa' => 'Infeksi saluran pencernaan ringan.'
        ]);
    }

    /**
     * IT-RM-DRM-005: Negatif - Pet Dihapus Sebelum Submit (Gambar 5)
     */
    public function test_input_rekam_medis_saat_pet_sudah_dihapus_harus_gagal()
    {
        $apt = TemuDokter::create([
            'idpet' => $this->pet->idpet,
            'idrole_user' => $this->idRoleUserDokter,
            'waktu_daftar' => now(),
            'status' => '0'
        ]);

        // Simulasi Pet dihapus (Soft Delete)
        $this->pet->delete();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.rekam-medis.store'), [
                'idreservasi_dokter' => $apt->idreservasi_dokter,
                'idpet' => $this->pet->idpet,
                'anamnesa' => 'Anamnesa test',
                'temuan_klinis' => 'Klinis test',
                'diagnosa' => 'Diagnosa test',
                'dokter_pemeriksa' => $this->idRoleUserDokter
            ]);

        $response->assertStatus(422); 
    }
}