<?php

namespace Tests\Feature;

use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTambahPetTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $roleAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role & Admin User
        $this->roleAdmin = Role::create(['nama_role' => 'Administrator']);
        $this->adminUser = User::factory()->create();

        DB::table('role_user')->insert([
            'iduser' => $this->adminUser->iduser,
            'idrole' => $this->roleAdmin->idrole,
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

    /** #Pet001: Admin berhasil menambah hewan peliharaan */
    public function test_admin_dapat_menambah_hewan_peliharaan()
    {
        $pemilik = Pemilik::factory()->create();

        $payload = [
            'nama' => 'Si Belang',
            'jenis_kelamin' => 'M',
            'idras_hewan' => 1,
            'idpemilik' => $pemilik->idpemilik,
            'tanggal_lahir' => '2023-01-01',
            'warna_tanda' => 'Putih corak hitam'
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertRedirect(route('data.pet.index'));
        $this->assertDatabaseHas('pet', [
            'nama' => 'Si Belang',
            'idpemilik' => $pemilik->idpemilik
        ]);
    }

    /** #Pet002: Gagal tambah Pet jika field wajib (nama) kosong */
    public function test_admin_gagal_tambah_pet_jika_nama_kosong()
    {
        $pemilik = Pemilik::factory()->create();

        $payload = [
            'nama' => '', 
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => $pemilik->idpemilik,
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertSessionHasErrors(['nama']);
    }

    /** #Pet003: Gagal tambah Pet jika jenis kelamin kosong */
    public function test_admin_gagal_tambah_pet_jika_jenis_kelamin_kosong()
    {
        $pemilik = Pemilik::factory()->create();

        $payload = [
            'nama' => 'Si Putih',
            'jenis_kelamin' => '', // Kosong
            'idras_hewan' => 1,
            'idpemilik' => $pemilik->idpemilik,
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertSessionHasErrors(['jenis_kelamin']);
    }

    /** #Pet004: Gagal tambah Pet jika ras hewan kosong */
    public function test_admin_gagal_tambah_pet_jika_ras_hewan_kosong()
    {
        $pemilik = Pemilik::factory()->create();

        $payload = [
            'nama' => 'Si Putih',
            'jenis_kelamin' => 'M',
            'idras_hewan' => '', // Kosong
            'idpemilik' => $pemilik->idpemilik,
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertSessionHasErrors(['idras_hewan']);
    }

    /** #Pet005: Gagal tambah Pet jika pemilik kosong */
    public function test_admin_gagal_tambah_pet_jika_pemilik_kosong()
    {
        $payload = [
            'nama' => 'Si Putih',
            'jenis_kelamin' => 'M',
            'idras_hewan' => 1,
            'idpemilik' => '', // Kosong
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->post(route('data.pet.index'), $payload);

        $response->assertSessionHasErrors(['idpemilik']);
    }
}