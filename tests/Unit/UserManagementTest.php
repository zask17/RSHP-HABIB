<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserManagementTest extends TestCase
{
    /**
     * 1. HASIL POSITIF: Menambahkan Pengguna Baru (#TC_P001)
     * Menguji fungsi store() dengan data valid.
     */
    public function test_store_user_success()
    {
        // Login sebagai admin agar bisa melewati middleware
        $admin = User::where('email', 'admin@mail.com')->first();

        $data = [
            'nama' => 'User Baru Test',
            'email' => 'newuser@rshp.com',
            'password' => 'password123',
        ];

        $response = $this->actingAs($admin)->post(route('data.users.store'), $data);

        // Ekspektasi: Redirect ke index dengan flash message sukses
        $response->assertStatus(302);
        $response->assertRedirect(route('data.users.index'));
        $response->assertSessionHas('success', 'Pengguna berhasil ditambahkan');

        // Pastikan data masuk ke database
        $this->assertDatabaseHas('user', [
            'email' => 'newuser@rshp.com',
            'nama' => 'User Baru Test'
        ]);
    }

    /**
     * 2. HASIL NEGATIF: Validasi Password Kurang dari 6 Karakter (#TC_N001)
     * Menguji aturan 'min:6' pada validasi store().
     */
    public function test_store_user_fails_password_too_short()
    {
        $admin = User::where('email', 'admin@mail.com')->first();

        $data = [
            'nama' => 'User Gagal',
            'email' => 'fail@rshp.com',
            'password' => '123', // Terlalu pendek
        ];

        $response = $this->actingAs($admin)->post(route('data.users.store'), $data);

        // Ekspektasi: Error pada field password
        $response->assertSessionHasErrors(['password']);
    }

    /**
     * 3. HASIL NEGATIF: Menghapus Akun Sendiri (#TC_N002)
     * Menguji proteksi logic pada fungsi destroy().
     */
    public function test_admin_cannot_delete_themselves()
    {
        // Login sebagai admin
        $admin = User::where('email', 'admin@mail.com')->first();

        // Mencoba menghapus ID sendiri (ID 6)
        $response = $this->actingAs($admin)->delete(route('data.users.destroy', $admin->iduser));

        // Ekspektasi: Redirect dengan pesan error
        $response->assertRedirect(route('data.users.index'));
        $response->assertSessionHas('error', 'Anda tidak dapat menghapus akun Anda sendiri');
        
        // Pastikan deleted_at tetap NULL (tidak terhapus)
        $this->assertDatabaseHas('user', [
            'iduser' => $admin->iduser,
            'deleted_at' => null
        ]);
    }

    /**
     * 4. HASIL NEGATIF: Akses User yang Sudah di Soft Delete (#TC_N003)
     * Menguji fungsi show() agar tidak menampilkan user yang sudah dihapus.
     */
    public function test_cannot_show_soft_deleted_user()
    {
        $admin = User::where('email', 'admin@mail.com')->first();

        // Mencari user yang sudah ada kolom deleted_at (seperti ID 24 di dump kamu)
        $deletedUser = User::whereNotNull('deleted_at')->first();

        if ($deletedUser) {
            $response = $this->actingAs($admin)->get(route('data.users.show', $deletedUser->iduser));

            // Ekspektasi: 404 karena findOrFail() + whereNull('deleted_at')
            $response->assertStatus(404);
        }
    }
}