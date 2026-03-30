<?php


namespace Tests\Feature;

use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
   use RefreshDatabase;
   protected $Administrator;

   protected function setUp(): void
   {
       parent::setUp();

       // Siapkan data Role
       Role::create([
           'idrole' => 1,
           'nama_role' => 'Administrator'
       ]);

       // Siapkan User Admin
       $this->Administrator = User::factory()->create();

       // Hubungkan User ke Role
       RoleUser::create([
           'iduser' => $this->Administrator->iduser,
           'idrole' => 1,
           'status' => true
       ]);
   }

   public function testUpdateUserBerhasil()
   {
       $user = User::factory()->create();


       $payload = [
           'nama' => 'Nama Baru',
           'email' => 'baru@example.com'
       ];


       // WAJIB: Tambahkan actingAs agar tidak dialihkan ke login
       $response = $this->actingAs($this->Administrator)
           ->withSession(['user_role' => 1])
           ->put(route('data.users.update', $user->iduser), $payload);


       $response->assertStatus(302);
       $response->assertRedirect(route('data.users.index'));
       $response->assertSessionHas('success', 'Pengguna berhasil diperbarui');


       $this->assertDatabaseHas('user', [
           'iduser' => $user->iduser,
           'nama' => 'Nama Baru',
           'email' => 'baru@example.com'
       ]);
   }

   public function testUpdateUserGagalEmailSudahTerdaftar()
   {
       $userLain = User::factory()->create(['email' => 'milik.orang@example.com']);
       $userTarget = User::factory()->create(['email' => 'target@example.com']);

       $payload = [
           'nama' => 'Edit Nama',
           'email' => 'milik.orang@example.com'
       ];

       //Tambahkan actingAs di sini juga
       $response = $this->actingAs($this->Administrator)
           ->withSession(['user_role' => 1])
           ->put(route('data.users.update', $userTarget->iduser), $payload);

       $response->assertStatus(302);
       $response->assertSessionHasErrors(['email']);

       $this->assertDatabaseHas('user', [
           'iduser' => $userTarget->iduser,
           'email' => 'target@example.com'
       ]);
   }

   
}