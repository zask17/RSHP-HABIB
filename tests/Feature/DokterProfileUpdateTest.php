<?php

namespace Tests\Feature;

use App\Models\Dokter;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DokterProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $dokterUser;
    protected $dokterProfile;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Buat role secara dinamis agar ID pasti valid di database
        $this->role = Role::create([
            'nama_role' => 'Dokter'
        ]);

        // 2. Buat user dokter
        $this->dokterUser = User::factory()->create([
            'nama' => 'Dokter Test',
            'email' => 'dokter@test.com'
        ]);

        // VERIFIKASI: user sudah tersimpan dengan ID
        $this->assertNotNull($this->dokterUser->iduser, 'User ID should not be null');

        // 3. Hubungkan user dengan role menggunakan ID dari objek role
        RoleUser::create([
            'iduser' => $this->dokterUser->iduser,
            'idrole' => $this->role->idrole,
            'status' => true
        ]);

        // 4. Buat profil dokter
        $this->dokterProfile = Dokter::create([
            'iduser' => $this->dokterUser->iduser,
            'alamat' => 'Alamat Lama',
            'no_hp' => '0812345678',
            'bidang_dokter' => 'Dokter Umum',
            'jenis_kelamin' => 'M'
        ]);
    }

    /** #PfD001: Menampilkan halaman edit profil */
    public function test_menampilkan_halaman_edit_profil_dokter()
    {
        $response = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->get(route('profile.edit', ['profileType' => 'dokter']));

        $response->assertStatus(200);
        $response->assertSee('Dokter Test');
        $response->assertSee('Alamat Lama');
    }

    /** #PfD002: Nama dan email tidak ikut berubah */
    public function test_nama_dan_email_tidak_ikut_terupdate()
    {
        $payload = [
            'nama' => 'Mencoba Ganti Nama',
            'alamat' => 'Alamat Baru',
            'no_hp' => '08123',
            'bidang_dokter' => 'Dokter Umum',
            'jenis_kelamin' => 'M'
        ];

        $response = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'dokter']), $payload);

        $this->assertDatabaseHas('user', [
            'iduser' => $this->dokterUser->iduser,
            'nama' => 'Dokter Test'
        ]);
    }

    /** #PfD003: Validasi no_hp harus angka */
    public function test_update_profil_gagal_jika_no_hp_bukan_angka()
    {
        $payload = [
            'alamat' => 'Alamat Valid',
            'no_hp' => 'abcdefgh',
            'bidang_dokter' => 'Dokter Umum',
            'jenis_kelamin' => 'M'
        ];

        $response = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'dokter']), $payload);

        $response->assertSessionHasErrors(['no_hp']);
    }

    /** #PfD004: Update profil berhasil */
    public function test_update_profil_dokter_berhasil()
    {
        $payload = [
            'alamat' => 'Jl. Mawar No. 10',
            'no_hp' => '089999999',
            'bidang_dokter' => 'Spesialis Jantung',
            'jenis_kelamin' => 'M'
        ];

        $response = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'dokter']), $payload);

        $response->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('dokter', [
            'iddokter' => $this->dokterProfile->iddokter,
            'alamat' => 'Jl. Mawar No. 10',
            'no_hp' => '089999999'
        ]);
    }

    /** #PfD005: Field alamat kosong */
    public function test_update_profil_gagal_jika_field_kosong()
    {
        $payload = [
            'alamat' => '',
            'no_hp' => '08123',
            'bidang_dokter' => 'Dokter Umum',
            'jenis_kelamin' => 'M'
        ];

        $response = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'dokter']), $payload);

        $response->assertSessionHasErrors(['alamat']);
    }

    /** #PfD006: Update gagal jika nomor telepon terlalu panjang */
    public function test_update_profil_gagal_jika_no_hp_melebihi_batas()
    {
        $payload = [
            'alamat' => 'Alamat Valid',
            'no_hp' => str_repeat('1', 46),
            'bidang_dokter' => 'Dokter Umum',
            'jenis_kelamin' => 'M'
        ];

        $response = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'dokter']), $payload);

        $response->assertSessionHasErrors(['no_hp']);
    }

    /** #PfD007: Update gagal jika jenis kelamin tidak sesuai enum */
    public function test_update_profil_gagal_jika_jenis_kelamin_tidak_valid()
    {
        $payload = [
            'alamat' => 'Alamat Valid',
            'no_hp' => '0812345678',
            'bidang_dokter' => 'Dokter Umum',
            'jenis_kelamin' => 'X' // Bukan M atau F
        ];

        $response = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'dokter']), $payload);

        $response->assertSessionHasErrors(['jenis_kelamin']);
    }

    /** #PfD008: Memastikan akses ditolak jika mencoba update profil dokter lain */
    public function test_tidak_bisa_update_profil_dokter_lain()
    {
        // Buat dokter lain
        $otherUser = User::factory()->create();
        $otherDoctor = Dokter::create([
            'iduser' => $otherUser->iduser,
            'alamat' => 'Alamat Dokter Lain',
            'no_hp' => '0811111111',
            'bidang_dokter' => 'Dokter Gigi',
            'jenis_kelamin' => 'F'
        ]);

        $payload = [
            'alamat' => 'Mencoba Ubah Alamat Orang Lain',
            'no_hp' => '0812345678',
            'bidang_dokter' => 'Spesialis',
            'jenis_kelamin' => 'F'
        ];

        $response = $this->actingAs($this->dokterUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('data.dokter.update', $otherDoctor->iddokter), $payload);

        // Data dokter lain di DB tidak boleh berubah
        $this->assertDatabaseMissing('dokter', [
            'iddokter' => $otherDoctor->iddokter,
            'alamat' => 'Mencoba Ubah Alamat Orang Lain'
        ]);
    }
}