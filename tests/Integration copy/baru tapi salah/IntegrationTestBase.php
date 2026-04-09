<?php

namespace Tests\Integration;

use App\Models\Role;
use App\Models\User;
use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\RasHewan;
use App\Models\JenisHewan;
use App\Models\RekamMedis;
use App\Models\TemuDokter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * BASE CLASS UNTUK INTEGRATION TESTS KELOMPOK 2
 * Menyediakan Actor, Master Data, dan Helper Workflow.
 */
abstract class IntegrationTestBase extends TestCase
{
    use RefreshDatabase;

    protected User $pemilik, $resepsionis, $dokter, $perawat, $admin;
    protected Role $rolePemilik, $roleResepsionis, $roleDokter, $rolePerawat, $roleAdmin;
    protected int $idRoleUserResepsionis, $idRoleUserDokter, $idRoleUserPerawat;
    protected Pet $pet;
    protected Pemilik $pemilikData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestActors();
        $this->setUpMasterData();
    }

    /**
     * Menyiapkan aktor-aktor sistem (User, Role, dan Role_User Mapping)
     */
    protected function setUpTestActors(): void
    {
        // 1. Buat Role
        $this->roleAdmin = Role::firstOrCreate(['nama_role' => 'Administrator']);
        $this->rolePemilik = Role::firstOrCreate(['nama_role' => 'Pemilik Hewan']);
        $this->roleResepsionis = Role::firstOrCreate(['nama_role' => 'Resepsionis']);
        $this->roleDokter = Role::firstOrCreate(['nama_role' => 'Dokter']);
        $this->rolePerawat = Role::firstOrCreate(['nama_role' => 'Perawat']);

        // 2. Buat User menggunakan factory
        $this->admin = User::factory()->create(['nama' => 'Admin Test']);
        $this->pemilik = User::factory()->create(['nama' => 'Pemilik Test']);
        $this->resepsionis = User::factory()->create(['nama' => 'Resepsionis Test']);
        $this->dokter = User::factory()->create(['nama' => 'Dokter Test']);
        $this->perawat = User::factory()->create(['nama' => 'Perawat Test']);

        // 3. Mapping User ke Role_User
        DB::table('role_user')->insert([
            ['iduser' => $this->admin->iduser, 'idrole' => $this->roleAdmin->idrole, 'status' => 1],
            ['iduser' => $this->pemilik->iduser, 'idrole' => $this->rolePemilik->idrole, 'status' => 1],
        ]);

        $this->idRoleUserResepsionis = DB::table('role_user')->insertGetId([
            'iduser' => $this->resepsionis->iduser, 'idrole' => $this->roleResepsionis->idrole, 'status' => 1
        ]);

        $this->idRoleUserDokter = DB::table('role_user')->insertGetId([
            'iduser' => $this->dokter->iduser, 'idrole' => $this->roleDokter->idrole, 'status' => 1
        ]);

        $this->idRoleUserPerawat = DB::table('role_user')->insertGetId([
            'iduser' => $this->perawat->iduser, 'idrole' => $this->rolePerawat->idrole, 'status' => 1
        ]);
    }

    protected function setUpMasterData(): void
    {
        $jenis = JenisHewan::create(['nama_jenis_hewan' => 'Anjing']);
        $ras = RasHewan::create(['nama_ras_hewan' => 'Golden', 'idjenis_hewan' => $jenis->idjenis_hewan]);
        $this->pemilikData = Pemilik::create(['nama_pemilik' => 'Budi', 'no_wa' => '08123', 'alamat' => 'Surabaya']);
        
        $this->pet = Pet::create([
            'nama' => 'Buddy',
            'tanggal_lahir' => '2020-01-01',
            'warna_tanda' => 'Coklat',
            'jenis_kelamin' => 'L',
            'idpemilik' => $this->pemilikData->idpemilik,
            'idras_hewan' => $ras->idras_hewan
        ]);
    }

    protected function createPendingAppointment(Pet $pet = null): TemuDokter
    {
        $targetPet = $pet ?? $this->pet;
        return TemuDokter::create([
            'idpet' => $targetPet->idpet,
            'idrole_user' => $this->idRoleUserDokter,
            'waktu_daftar' => now(),
            'no_urut' => 1,
            'status' => 'M'
        ]);
    }
}