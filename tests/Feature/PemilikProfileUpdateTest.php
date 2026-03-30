<?php

namespace Tests\Feature;

use App\Models\Pemilik;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Nama class HARUS SAMA dengan nama file: PemilikProfileUpdateTest
class PemilikProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $pemilikUser;
    protected $pemilikProfile;
    protected $role;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Buat role Pemilik
        $this->role = Role::create([
            'nama_role' => 'Pemilik'
        ]);

        // 2. Buat user pemilik
        $this->pemilikUser = User::factory()->create([
            'nama' => 'Pemilik Test',
            'email' => 'pemilik@test.com'
        ]);

        // 3. Hubungkan user dengan role
        DB::table('role_user')->insert([
            'iduser' => $this->pemilikUser->iduser,
            'idrole' => $this->role->idrole,
            'status' => 1
        ]);

        // 4. Buat profil pemilik (idpemilik diisi manual sesuai model karena incrementing = false)
        $this->pemilikProfile = Pemilik::create([
            'idpemilik' => 1,
            'iduser' => $this->pemilikUser->iduser,
            'no_wa' => '0812345678',
            'alamat' => 'Alamat Pemilik Lama'
        ]);
    }

    /** #PfPm001: Menampilkan halaman edit profil */
    public function test_menampilkan_halaman_edit_profil_pemilik()
    {
        $response = $this->actingAs($this->pemilikUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->get(route('profile.edit', ['profileType' => 'pemilik']));

        $response->assertStatus(200);
        $response->assertSee('Pemilik Test');
    }

    /** #PfPm002: Update profil berhasil */
    public function test_update_profil_pemilik_berhasil()
    {
        $payload = [
            'nama' => 'Pemilik Test Updated',
            'email' => 'pemilik_baru@test.com',
            'no_wa' => '089999999',
            'alamat' => 'Alamat Pemilik Baru'
        ];

        $response = $this->actingAs($this->pemilikUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'pemilik']), $payload);

        $response->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('pemilik', [
            'idpemilik' => $this->pemilikProfile->idpemilik,
            'no_wa' => '089999999'
        ]);
    }

    /** #PfPm003: Validasi nomor WA wajib diisi (Field Kosong) */
    public function test_update_profil_gagal_jika_no_wa_kosong()
    {
        $payload = [
            'nama' => 'Test',
            'email' => 'test@mail.com',
            'no_wa' => '', // Dikosongkan
            'alamat' => 'Alamat Valid'
        ];

        $response = $this->actingAs($this->pemilikUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'pemilik']), $payload);

        $response->assertSessionHasErrors(['no_wa']);
    }

    /** #PfPm004: Validasi alamat wajib diisi (Field Kosong) */
    public function test_update_profil_gagal_jika_alamat_kosong()
    {
        $payload = [
            'nama' => 'Test',
            'email' => 'test@mail.com',
            'no_wa' => '0812345678',
            'alamat' => '' // Dikosongkan
        ];

        $response = $this->actingAs($this->pemilikUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'pemilik']), $payload);

        $response->assertSessionHasErrors(['alamat']);
    }

    /** #PfPm005: Validasi no_wa harus angka */
    public function test_update_profil_gagal_jika_no_wa_bukan_angka()
    {
        $payload = [
            'nama' => 'Pemilik Test',
            'email' => 'pemilik@test.com',
            'no_wa' => 'abcdefghij', 
            'alamat' => 'Alamat Valid'
        ];

        $response = $this->actingAs($this->pemilikUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'pemilik']), $payload);

        $response->assertSessionHasErrors(['no_wa']);
    }

    /** #PfPm006: Validasi batas maksimal karakter No WA */
    public function test_update_profil_gagal_jika_no_wa_melebihi_batas()
    {
        $payload = [
            'nama' => 'Pemilik Test',
            'email' => 'pemilik@test.com',
            'no_wa' => str_repeat('1', 46), 
            'alamat' => 'Alamat Valid'
        ];

        $response = $this->actingAs($this->pemilikUser)
            ->withSession(['user_role' => $this->role->idrole])
            ->put(route('profile.update', ['profileType' => 'pemilik']), $payload);

        $response->assertSessionHasErrors(['no_wa']);
    }
}