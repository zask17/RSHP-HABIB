<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\TemuDokter;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class TemuDokterKosongTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $dokter;
    private $pet;
    private $dokterRoleUser;

    /**
     * SETUP: Persiapan data awal (Role, User, dan Pet)
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Inisialisasi Role sesuai sistem RSHP
        $adminRole = Role::create(['nama_role' => 'Administrator']);
        $dokterRole = Role::create(['nama_role' => 'Dokter']);

        // 2. Inisialisasi User (Admin dan Dokter)
        $this->admin = User::factory()->create(['nama' => 'Admin RSHP']);
        $this->admin->roles()->attach($adminRole->idrole, ['status' => 1]);

        $this->dokter = User::factory()->create(['nama' => 'Dr. Budi Santoso']);
        $this->dokter->roles()->attach($dokterRole->idrole, ['status' => 1]);
        
        // Ambil data role_user untuk kebutuhan foreign key
        $this->dokterRoleUser = DB::table('role_user')
            ->where('iduser', $this->dokter->iduser)
            ->where('idrole', $dokterRole->idrole)
            ->first();

        // 3. Inisialisasi Pemilik & Pet sebagai data pendukung
        DB::table('pemilik')->insert([
            'idpemilik' => 1,
            'iduser' => $this->admin->iduser,
            'no_wa' => '08123456789',
            'alamat' => 'Surabaya'
        ]);

        DB::table('jenis_hewan')->insert(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Kucing']);
        DB::table('ras_hewan')->insert(['idras_hewan' => 1, 'nama_ras' => 'Persia', 'idjenis_hewan' => 1]);

        $this->pet = Pet::create([
            'nama' => 'Whiskers',
            'idpemilik' => 1,
            'idras_hewan' => 1,
            'jenis_kelamin' => 'M',
            'warna_tanda' => 'Orange'
        ]);
    }

    /**
     * SKENARIO: Memastikan data Temu Dokter TIDAK TERSIMPAN jika field wajib kosong
     */
    public function test_temu_dokter_tidak_tersimpan_jika_field_kosong()
    {
        // 1. Kirim data kosong
        $invalidData = [
            'idrole_user' => null,
            'waktu_daftar' => null,
            'no_urut' => null
        ];

        // 2. Lakukan request POST ke fungsi store
        $response = $this->actingAs($this->admin)
            ->from(route('data.temu-dokter.index'))
            ->post(route('data.temu-dokter.store'), $invalidData);

        // --- VERIFIKASI (ASSERTIONS) ---

        // Memastikan sistem merespons dengan redirect (kembali ke form karena error)
        $response->assertStatus(302);
        
        // Memastikan session mencatat error untuk field yang wajib diisi
        $response->assertSessionHasErrors(['idrole_user', 'waktu_daftar']);

        // Memastikan jumlah data di tabel 'temu_dokter' tetap 0
        $this->assertDatabaseCount('temu_dokter', 0);
        
        // Memastikan tidak ada record yang mengandung data null tersebut
        $this->assertDatabaseMissing('temu_dokter', [
            'idrole_user' => null
        ]);
    }
}