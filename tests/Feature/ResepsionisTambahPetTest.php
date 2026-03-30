<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResepsionisTambahPetTest extends TestCase
{
    use RefreshDatabase;

    protected $resepsionisUser;
    protected $roleResepsionis;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role & User Resepsionis
        $this->roleResepsionis = Role::create(['nama_role' => 'Resepsionis']);
        $this->resepsionisUser = User::factory()->create();

        DB::table('role_user')->insert([
            'iduser' => $this->resepsionisUser->iduser,
            'idrole' => $this->roleResepsionis->idrole,
            'status' => 1
        ]);

        // 2. Setup Data Master Hewan
        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Kucing']);
        DB::table('ras_hewan')->insert([
            'idras_hewan' => 1,
            'nama_ras' => 'Persia',
            'idjenis_hewan' => 1
        ]);
    }

    /** #PetR001: Gagal tambah Pet jika field nama kosong */
    public function test_resepsionis_gagal_tambah_pet_jika_nama_kosong()
    {
        $pemilik = Pemilik::factory()->create();

        $payload = [
            'nama' => '', // Nama dikosongkan
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => $pemilik->idpemilik,
        ];

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->post(route('data.pet.index'), $payload);

        // Memastikan muncul error validation pada field 'nama'
        $response->assertSessionHasErrors(['nama']);
    }

    /** #PetR002: Resepsionis berhasil menambah hewan peliharaan */
    public function test_resepsionis_dapat_menambah_hewan_peliharaan()
    {
        $pemilik = Pemilik::factory()->create();

        $payload = [
            'nama' => 'Muezza',
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => $pemilik->idpemilik,
            'tanggal_lahir' => '2024-01-01',
            'warna_tanda' => 'Orange'
        ];

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertRedirect(route('data.pet.index'));
        $this->assertDatabaseHas('pet', [
            'nama' => 'Muezza',
            'idpemilik' => $pemilik->idpemilik
        ]);
    }

    /** #PetR003: Gagal jika Jenis Kelamin kosong */
    public function test_resepsionis_gagal_tambah_pet_jika_jenis_kelamin_kosong()
    {
        $pemilik = Pemilik::factory()->create();

        $payload = [
            'nama' => 'Si Putih',
            'jenis_kelamin' => '',
            'idras_hewan' => 1,
            'idpemilik' => $pemilik->idpemilik,
        ];

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertSessionHasErrors(['jenis_kelamin']);
    }

    /** #PetR004: Gagal jika Ras Hewan kosong */
    public function test_resepsionis_gagal_tambah_pet_jika_ras_hewan_kosong()
    {
        $pemilik = Pemilik::factory()->create();

        $payload = [
            'nama' => 'Si Putih',
            'jenis_kelamin' => 'M',
            'idras_hewan' => '',
            'idpemilik' => $pemilik->idpemilik,
        ];

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertSessionHasErrors(['idras_hewan']);
    }

    /** #PetR005: Gagal jika Pemilik tidak dipilih */
    public function test_resepsionis_gagal_tambah_pet_jika_pemilik_kosong()
    {
        $payload = [
            'nama' => 'Si Putih',
            'jenis_kelamin' => 'M',
            'idras_hewan' => 1,
            'idpemilik' => '',
        ];

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertSessionHasErrors(['idpemilik']);
    }
}
