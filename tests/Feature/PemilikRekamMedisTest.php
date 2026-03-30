<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\RekamMedis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PemilikRekamMedisTest extends TestCase
{
    use RefreshDatabase;

    protected $userPemilik;
    protected $pemilikProfile;
    protected $dokterRoleUserId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role Pemilik dan User Pemilik
        $rolePemilik = Role::create(['nama_role' => 'Pemilik']);
        $this->userPemilik = User::factory()->create();
        DB::table('role_user')->insert([
            'iduser' => $this->userPemilik->iduser, 
            'idrole' => $rolePemilik->idrole, 
            'status' => 1
        ]);
        
        // 2. Setup Profil Pemilik (Wajib ada untuk relasi logic di Controller)
        $this->pemilikProfile = Pemilik::create([
            'idpemilik' => rand(100, 999),
            'iduser' => $this->userPemilik->iduser,
            'no_wa' => '08123456789',
            'alamat' => 'Sidoarjo'
        ]);

        // 3. Setup Role Dokter untuk keperluan foreign key dokter_pemeriksa
        $roleDokter = Role::create(['nama_role' => 'Dokter']);
        $dokterUser = User::factory()->create();
        $this->dokterRoleUserId = DB::table('role_user')->insertGetId([
            'iduser' => $dokterUser->iduser,
            'idrole' => $roleDokter->idrole,
            'status' => 1
        ]);
    }

    /** #PemilikRM001: Pemilik dapat melihat daftar rekam medis hewan miliknya */
    public function test_pemilik_dapat_melihat_daftar_rekam_medis()
    {
        $this->withoutExceptionHandling();

        $myPet = Pet::factory()->create(['idpemilik' => $this->pemilikProfile->idpemilik]);
        RekamMedis::factory()->create([
            'idpet' => $myPet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->userPemilik)
            ->get(route('data.rekam-medis.index'));

        $response->assertStatus(200);
    }

    /** #PemilikRM002: Pemilik dapat melihat detail rekam medis miliknya */
    public function test_pemilik_dapat_melihat_show_rekam_medis_milik_sendiri()
    {
        $this->withoutExceptionHandling();

        $myPet = Pet::factory()->create(['idpemilik' => $this->pemilikProfile->idpemilik]);
        $rm = RekamMedis::factory()->create([
            'idpet' => $myPet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->userPemilik)
            ->get(route('data.rekam-medis.show', $rm->idrekam_medis));

        $response->assertStatus(200);
    }

    /** #PemilikRM003: Proteksi - Pemilik dilarang melihat rekam medis pet orang lain */
    public function test_pemilik_tidak_dapat_melihat_rekam_medis_pet_orang_lain()
    {
        // Pet milik orang lain (idpemilik berbeda)
        $otherPet = Pet::factory()->create(['idpemilik' => 9999]); 
        $rmOther = RekamMedis::factory()->create([
            'idpet' => $otherPet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->userPemilik)
            ->get(route('data.rekam-medis.show', $rmOther->idrekam_medis));

        // Berdasarkan Controller, akan diredirect kembali ke index dengan error
        $response->assertRedirect(route('data.rekam-medis.index'));
        $response->assertSessionHas('error');
    }
}