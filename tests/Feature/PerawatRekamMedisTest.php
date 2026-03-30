<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Pet;
use App\Models\RekamMedis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerawatRekamMedisTest extends TestCase
{
    use RefreshDatabase;

    protected $perawat;
    protected $rolePerawat;
    protected $dokterRoleUserId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role Perawat dan User Perawat
        $this->rolePerawat = Role::create(['nama_role' => 'Perawat']);
        $this->perawat = User::factory()->create();

        DB::table('role_user')->insert([
            'iduser' => $this->perawat->iduser,
            'idrole' => $this->rolePerawat->idrole,
            'status' => 1
        ]);

        // 2. Setup Role Dokter (sebagai referensi dokter pemeriksa)
        $roleDokter = Role::create(['nama_role' => 'Dokter']);
        $dokterUser = User::factory()->create();
        $this->dokterRoleUserId = DB::table('role_user')->insertGetId([
            'iduser' => $dokterUser->iduser,
            'idrole' => $roleDokter->idrole,
            'status' => 1
        ]);
    }

    /** #PerawatRM001: Perawat dapat melihat daftar rekam medis */
    public function test_perawat_dapat_melihat_daftar_rekam_medis()
    {
        $this->withoutExceptionHandling();

        $response = $this->actingAs($this->perawat)
            ->get(route('data.rekam-medis.index'));

        $response->assertStatus(200);
        $response->assertViewIs('data.rekam-medis.index');
    }

    /** #PerawatRM002: Perawat dapat mengakses form edit data utama */
    public function test_perawat_dapat_mengakses_form_edit_data_utama()
    {
        $this->withoutExceptionHandling();

        $pet = Pet::factory()->create();
        $rm = RekamMedis::factory()->create([
            'idpet' => $pet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->perawat)
            ->get(route('data.rekam-medis.edit-data', $rm->idrekam_medis));

        $response->assertStatus(200);
    }

    /** #PerawatRM003: Perawat berhasil memperbarui data utama rekam medis */
    public function test_perawat_berhasil_update_data_utama()
    {
        $this->withoutExceptionHandling();

        $pet = Pet::factory()->create();
        $rm = RekamMedis::factory()->create([
            'idpet' => $pet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId
        ]);

        $newPet = Pet::factory()->create();
        $payload = [
            'anamnesa' => 'Anamnesa diubah Perawat',
            'temuan_klinis' => 'Temuan diubah Perawat',
            'diagnosa' => 'Diagnosa Baru oleh Perawat',
            'idpet' => $newPet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId,
        ];

        $response = $this->actingAs($this->perawat)
            ->put(route('data.rekam-medis.update-data', $rm->idrekam_medis), $payload);

        $response->assertRedirect(route('data.rekam-medis.index'));
        $this->assertDatabaseHas('rekam_medis', [
            'idrekam_medis' => $rm->idrekam_medis,
            'diagnosa' => 'Diagnosa Baru oleh Perawat'
        ]);
    }

    /** #PerawatRM004: Perawat dapat melihat detail rekam medis (Show) */
    public function test_perawat_dapat_melihat_show_rekam_medis()
    {
        $this->withoutExceptionHandling();

        $rm = RekamMedis::factory()->create(['dokter_pemeriksa' => $this->dokterRoleUserId]);

        $response = $this->actingAs($this->perawat)
            ->get(route('data.rekam-medis.show', $rm->idrekam_medis));

        $response->assertStatus(200);
    }

    /** #PerawatRM005: Perawat TIDAK dapat mengakses form edit detail tindakan (Khusus Dokter) */
    public function test_perawat_tidak_dapat_mengakses_form_edit_detail_tindakan()
    {
        $rm = RekamMedis::factory()->create(['dokter_pemeriksa' => $this->dokterRoleUserId]);

        $response = $this->actingAs($this->perawat)
            ->get(route('data.rekam-medis.edit-detail', $rm->idrekam_medis));

        // Berdasarkan middleware 'role:Administrator,Dokter' di web.php
        $response->assertStatus(403); 
    }

    /** #PerawatRM006: Perawat TIDAK dapat memperbarui detail tindakan */
    public function test_perawat_tidak_dapat_update_detail_tindakan()
    {
        $rm = RekamMedis::factory()->create(['dokter_pemeriksa' => $this->dokterRoleUserId]);

        $payload = [
            'detail_tindakan' => [
                ['idkode_tindakan_terapi' => 1, 'detail' => 'Ilegal update']
            ]
        ];

        $response = $this->actingAs($this->perawat)
            ->put(route('data.rekam-medis.update-detail', $rm->idrekam_medis), $payload);

        $response->assertStatus(403);
    }
}