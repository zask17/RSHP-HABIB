<?php

namespace Tests\System;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoginSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::insert([
            ['nama_role' => 'Administrator'],
            ['nama_role' => 'Dokter'],
            ['nama_role' => 'Perawat'],
            ['nama_role' => 'Resepsionis'],
            ['nama_role' => 'Pemilik'],
        ]);
    }

    /**
     * Helper method untuk membuat user beserta rolenya.
     */
    private function createUserWithRole($roleName, $email, $password)
    {
        $user = User::factory()->create([
            'email' => $email,
            'password' => bcrypt($password),
        ]);

        if ($roleName) {
            $role = Role::where('nama_role', $roleName)->first();
            if ($role) {
                DB::table('role_user')->insert([
                    'iduser' => $user->iduser ?? $user->id,
                    'idrole' => $role->idrole ?? $role->id,
                    'status' => 1,
                ]);
            }
        }

        return $user;
    }

    /**
     * TC-01: (Positif) Login dengan akun Administrator
     */
    public function test_login_berhasil_sebagai_administrator()
    {
        $this->createUserWithRole('Administrator', 'admin@gmail.com', 'admin123');

        $response = $this->post('/login', [
            'email' => 'admin@gmail.com',
            'password' => 'admin123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('data.dashboard'));
    }

    /**
     * TC-02: (Positif) Login dengan akun Dokter
     */
    public function test_login_berhasil_sebagai_dokter()
    {
        $this->createUserWithRole('Dokter', 'dokter@gmail.com', 'dokter123');

        $response = $this->post('/login', [
            'email' => 'dokter@gmail.com',
            'password' => 'dokter123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('data.dashboard'));
    }

    /**
     * TC-03: (Positif) Login dengan akun Resepsionis
     */
    public function test_login_berhasil_sebagai_resepsionis()
    {
        $this->createUserWithRole('Resepsionis', 'resepsionis@gmail.com', 'resepsionis123');

        $response = $this->post('/login', [
            'email' => 'resepsionis@gmail.com',
            'password' => 'resepsionis123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('data.dashboard'));
    }

    /**
     * TC-04: (Positif) Login dengan akun Perawat
     */
    public function test_login_berhasil_sebagai_perawat()
    {
        $this->createUserWithRole('Perawat', 'perawat@gmail.com', 'perawat');

        $response = $this->post('/login', [
            'email' => 'perawat@gmail.com',
            'password' => 'perawat',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('data.dashboard'));
    }

    /**
     * TC-05: (Positif) Login dengan akun Pemilik
     */
    public function test_login_berhasil_sebagai_pemilik()
    {
        $this->createUserWithRole('Pemilik', 'pemilik@gmail.com', 'pemilik');

        $response = $this->post('/login', [
            'email' => 'pemilik@gmail.com',
            'password' => 'pemilik',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('data.dashboard'));
    }

    /**
     * TC-06: (Positif/Negatif) Login dengan akun user tanpa tabel Role
     */
    public function test_login_akun_tanpa_role()
    {
        $this->createUserWithRole(null, 'dokterbaru@gmail.com', 'dokter123');

        $response = $this->post('/login', [
            'email' => 'dokterbaru@gmail.com',
            'password' => 'dokter123',
        ]);

        $this->assertAuthenticated();
        // Berhasil autentikasi, dan aplikasi biasanya akan meredirect ke route utama. 
        // Anda mungkin menerima status forbidden nantinya di middleware, tapi login di auth flow ini berhasil.
        $response->assertRedirect(route('data.dashboard'));
    }

    /**
     * TC-07: (Negatif) Login dengan Password salah
     */
    public function test_login_gagal_karena_password_salah()
    {
        $this->createUserWithRole('Administrator', 'admin@gmail.com', 'admin123');

        $response = $this->post('/login', [
            'email' => 'admin@gmail.com',
            'password' => 'salahpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email'); // Laravel breeze message standar menyatu ke 'email'
    }

    /**
     * TC-08: (Negatif) Login dengan Email tidak terdaftar
     */
    public function test_login_gagal_karena_email_tidak_terdaftar()
    {
        $response = $this->post('/login', [
            'email' => 'tidakada@gmail.com',
            'password' => 'admin123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * TC-09: (Negatif) Form Submit dengan Email kosong
     */
    public function test_login_gagal_karena_email_kosong()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'admin123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * TC-10: (Negatif) Form Submit dengan Password kosong
     */
    public function test_login_gagal_karena_password_kosong()
    {
        $response = $this->post('/login', [
            'email' => 'admin@gmail.com',
            'password' => '',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
    }
}
