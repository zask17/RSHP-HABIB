<?php

namespace Tests\Feature;

use App\Models\Perawat;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerawatProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $perawatUser;
    protected $perawatProfile;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Buat role Perawat
        $this->role = Role::create([
            'nama_role' => 'Perawat'
        ]);

        // 2. Buat user perawat
        $this->perawatUser = User::factory()->create([
            'nama' => 'Perawat Test',
            'email' => 'perawat@test.com'
        ]);

        // 3. Hubungkan user dengan role
        RoleUser::create([
            'iduser' => $this->perawatUser->iduser,
            'idrole' => $this->role->idrole,
            'status' => true
        ]);

        // 4. Buat profil perawat
        $this->perawatProfile = Perawat::create([
            'iduser' => $this->perawatUser->iduser,
            'alamat' => 'Alamat Lama',
            'no_hp' => '0812345678',
            'pendidikan' => 'D3 Keperawatan',
            'jenis_kelamin' => 'F'
        ]);
    }

    /** #PfPwt001: Menampilkan halaman edit profil perawat */
    public function test_menampilkan_halaman_edit_profil_perawat()
    {
        $response = $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->get(route('profile.edit', ['profileType' => 'perawat']));

        $response->assertStatus(200);
        $response->assertSee('Perawat Test');
        $response->assertSee('Alamat Lama');
        $response->assertSee('D3 Keperawatan');
    }

    /** #PfPwt002: Nama dan email tidak ikut berubah */
    public function test_nama_dan_email_tidak_ikut_terupdate_perawat()
    {
        $payload = [
            'nama' => 'Ganti Nama Perawat',
            'alamat' => 'Alamat Baru',
            'no_hp' => '08123',
            'pendidikan' => 'S1 Keperawatan',
            'jenis_kelamin' => 'F'
        ];

        $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'perawat']), $payload);

        // Pastikan di tabel user, nama tetap sama
        $this->assertDatabaseHas('user', [
            'iduser' => $this->perawatUser->iduser,
            'nama' => 'Perawat Test'
        ]);
    }

    /** #PfPwt003: Validasi no_hp harus angka */
    public function test_update_profil_perawat_gagal_jika_no_hp_bukan_angka()
    {
        $payload = [
            'alamat' => 'Alamat Valid',
            'no_hp' => 'bukanangka',
            'pendidikan' => 'D3 Keperawatan',
            'jenis_kelamin' => 'F'
        ];

        $response = $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'perawat']), $payload);

        $response->assertSessionHasErrors(['no_hp']);
    }

    /** #PfPwt004: Validasi no_hp melebihi batas maksimal */
    public function test_update_profil_gagal_jika_no_hp_melebihi_batas()
    {
        // Payload dengan no_hp sebanyak 46 karakter (melebihi batas VARCHAR 45)
        $payload = [
            'alamat' => 'Alamat Valid',
            'no_hp' => str_repeat('1', 46),
            'pendidikan' => 'D3 Keperawatan',
            'jenis_kelamin' => 'F'
        ];

        // Mengirimkan request sebagai perawat yang sedang login
        $response = $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'perawat']), $payload);

        // Memastikan sistem mengembalikan error validasi pada field no_hp
        $response->assertSessionHasErrors(['no_hp']);
    }
    /** #PfPwt005: Update profil perawat berhasil */
    public function test_update_profil_perawat_berhasil()
    {
        $payload = [
            'alamat' => 'Jl. Melati No. 5',
            'no_hp' => '081222333444',
            'pendidikan' => 'S1 Ners',
            'jenis_kelamin' => 'F'
        ];

        $response = $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'perawat']), $payload);

        $response->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('perawat', [
            'idperawat' => $this->perawatProfile->idperawat,
            'alamat' => 'Jl. Melati No. 5',
            'pendidikan' => 'S1 Ners'
        ]);
    }

    /** #PfPwt006: Field pendidikan kosong */
    public function test_update_profil_perawat_gagal_jika_pendidikan_kosong()
    {
        $payload = [
            'alamat' => 'Alamat Valid',
            'no_hp' => '08123',
            'pendidikan' => '',
            'jenis_kelamin' => 'F'
        ];

        $response = $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'perawat']), $payload);

        $response->assertSessionHasErrors(['pendidikan']);
    }

    /** #PfPwt007: Keamanan - Tidak bisa update profil perawat lain */
    public function test_tidak_bisa_update_profil_perawat_lain()
    {
        // Buat perawat lain
        $otherPerawat = Perawat::factory()->create();

        $payload = [
            'alamat' => 'Mencoba Bajak Alamat',
            'no_hp' => '08999',
            'pendidikan' => 'S1',
            'jenis_kelamin' => 'M'
        ];

        // Mencoba mengirim request ke ID perawat lain melalui route data (admin side)
        // karena route profile.update biasanya berbasis user yang login
        $response = $this->actingAs($this->perawatUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('data.perawat.update', $otherPerawat->idperawat), $payload);

        $this->assertDatabaseMissing('perawat', [
            'idperawat' => $otherPerawat->idperawat,
            'alamat' => 'Mencoba Bajak Alamat'
        ]);
    }
}
