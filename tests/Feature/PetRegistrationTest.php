<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Pemilik;

class PetRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $resepsionisUser;
    protected $idPemilik;
    protected $idRas;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Master Data (Sesuai ID di SQL Dump)
        $roleAdmin = Role::create(['idrole' => 1, 'nama_role' => 'Administrator']);
        $roleResep = Role::create(['idrole' => 4, 'nama_role' => 'Resepsionis']);
        $rolePemilik = Role::create(['idrole' => 5, 'nama_role' => 'Pemilik']);
        $roleDokter = Role::create(['idrole' => 2, 'nama_role' => 'Dokter']);

        // 2. Setup Users
        $this->adminUser = User::create([
            'iduser' => 11, 'nama' => 'Admin', 'email' => 'admin@gmail.com', 'password' => Hash::make('pass')
        ]);
        DB::table('role_user')->insert(['iduser' => 11, 'idrole' => 1]);

        $this->resepsionisUser = User::create([
            'iduser' => 27, 'nama' => 'Resep', 'email' => 'resep@gmail.com', 'password' => Hash::make('pass')
        ]);
        DB::table('role_user')->insert(['iduser' => 27, 'idrole' => 4]);

        // 3. Setup Pemilik & Ras (Data wajib untuk FK)
        $userPemilik = User::create(['iduser' => 22, 'nama' => 'Budi', 'email' => 'budi@mail.com', 'password' => 'pass']);
        DB::table('role_user')->insert(['iduser' => 22, 'idrole' => 5]);
        $this->idPemilik = 3;
        Pemilik::create(['idpemilik' => $this->idPemilik, 'iduser' => 22, 'no_wa' => '0812', 'alamat' => 'Sby']);

        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Anjing']);
        $this->idRas = 1;
        DB::table('ras_hewan')->insert(['idras_hewan' => $this->idRas, 'nama_ras' => 'Golden', 'idjenis_hewan' => 1]);
    }

    /** #Pet001: Admin berhasil tambah pet */
    public function test_admin_berhasil_tambah_pet()
    {
        $payload = [
            'nama' => 'Buddy',
            'jenis_kelamin' => 'M',
            'idpemilik' => $this->idPemilik,
            'idras_hewan' => $this->idRas,
            'tanggal_lahir' => '2023-01-01'
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => 1])
            ->post(route('data.pet.store'), $payload);

        $response->assertRedirect(route('data.pet.index'));
        $this->assertDatabaseHas('pet', ['nama' => 'Buddy', 'idpemilik' => $this->idPemilik]);
    }

    /** #Pet002: Gagal jika nama kosong */
    public function test_gagal_tambah_pet_jika_nama_kosong()
    {
        $payload = ['nama' => '', 'jenis_kelamin' => 'M', 'idpemilik' => $this->idPemilik, 'idras_hewan' => $this->idRas];

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => 4])
            ->post(route('data.pet.store'), $payload);

        $response->assertSessionHasErrors(['nama']);
    }
}