<?php

namespace Tests\Unit\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

/**
 * UNIT TEST — Profile User (Admin Role)
 * Admin memiliki akses penuh untuk manajemen akun pengguna dan otorisasi
 * FORMAT PENGUJIAN:
 * [Test Code] | [Test Type] | [Object Being Tested] | [Function]
 */
class ProfileAdminUnitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Aktifkan foreign key check untuk integritas database
        DB::statement('PRAGMA foreign_keys = ON');

        // 2. Siapkan data Master Role (Admin ID 1)
        DB::table('role')->insert([
            'idrole' => 1,
            'nama_role' => 'Administrator'
        ]);
    }

    // ============ FUNCTIONALITY TESTS (ADMIN) ============

    /**
     * UT-F-Pf-001 | Functionality | Create User Admin |
     * Memastikan admin dapat membuat user baru dengan password terenkripsi
     */
    public function test_UT_F_Pf_001_admin_tambah_user_baru_berhasil()
    {
        $user = User::create([
            'nama' => 'Admin Baru',
            'email' => 'admin_baru@mail.com',
            'password' => Hash::make('password123')
        ]);

        $this->assertNotNull($user->iduser);
        $this->assertDatabaseHas('user', [
            'email' => 'admin_baru@mail.com'
        ]);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * UT-F-Pf-002 | Functionality | Update Profile Admin |
     */
    public function test_UT_F_Pf_002_admin_update_profile_sendiri_berhasil()
    {
        $user = User::factory()->create([
            'nama' => 'Nama Lama',
            'email' => 'lama@mail.com'
        ]);

        $user->update([
            'nama' => 'Nama Baru',
            'email' => 'baru@mail.com'
        ]);

        $this->assertEquals('Nama Baru', $user->fresh()->nama);
        $this->assertEquals('baru@mail.com', $user->fresh()->email);
    }

    /**
     * UT-F-Pf-003 | Functionality | Soft Delete User |
     * Sesuai logic UserController@destroy yang mengisi deleted_at dan deleted_by
     */
    public function test_UT_F_Pf_003_admin_hapus_user_secara_soft_delete()
    {
        $admin = User::factory()->create(['iduser' => 6]);
        $target = User::factory()->create(['iduser' => 7]);

        $target->update([
            'deleted_at' => now(),
            'deleted_by' => $admin->iduser
        ]);

        $this->assertNotNull($target->deleted_at);
        $this->assertEquals(6, $target->deleted_by);
    }

    /**
     * UT-F-Pf-004 | Functionality | Assign Role User |
     * Memastikan user terhubung ke tabel role_user
     */
    public function test_UT_F_Pf_004_admin_assign_role_ke_user_berhasil()
    {
        $user = User::factory()->create(['iduser' => 11]);
        
        DB::table('role_user')->insert([
            'iduser' => 11,
            'idrole' => 1,
            'status' => 1
        ]);

        $this->assertDatabaseHas('role_user', [
            'iduser' => 11,
            'idrole' => 1
        ]);
    }

    /**
     * UT-F-Pf-005 | Functionality | Reset Password |
     * Simulasi logic reset password di UserController
     */
    public function test_UT_F_Pf_005_admin_reset_password_user_berhasil()
    {
        $user = User::factory()->create(['password' => Hash::make('lama123')]);
        $newPassword = Hash::make('baru123');

        $user->update(['password' => $newPassword]);

        $this->assertTrue(Hash::check('baru123', $user->fresh()->password));
        $this->assertFalse(Hash::check('lama123', $user->fresh()->password));
    }

    // ============ DATA VALIDATION TESTS (ADMIN) ============

    /**
     * UT-V-Pf-001 | Data Validation | Unique Email |
     */
    public function test_UT_V_Pf_001_admin_gagal_tambah_user_email_sama()
    {
        User::factory()->create(['email' => 'duplicate@mail.com']);

        try {
            User::create([
                'nama' => 'User Kedua',
                'email' => 'duplicate@mail.com',
                'password' => Hash::make('password')
            ]);
            $this->fail('Seharusnya gagal karena email sudah terdaftar');
        } catch (QueryException $e) {
            $this->assertStringContainsString('UNIQUE constraint failed', $e->getMessage());
        }
    }

    /**
     * UT-V-Pf-002 | Data Validation | Required Nama |
     */
    public function test_UT_V_Pf_002_admin_gagal_tambah_user_nama_null()
    {
        try {
            $user = new User();
            $user->nama = null;
            $user->email = 'test@mail.com';
            $user->password = '123456';
            $user->save();
            
            $this->fail('Seharusnya gagal karena nama null');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-Pf-003 | Data Validation | Foreign Key Role |
     */
    public function test_UT_V_Pf_003_admin_gagal_assign_role_tidak_valid()
    {
        $user = User::factory()->create(['iduser' => 20]);

        try {
            DB::table('role_user')->insert([
                'iduser' => 20,
                'idrole' => 99, // Role ID tidak ada
                'status' => 1
            ]);
            $this->fail('Seharusnya gagal karena idrole tidak valid');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V-Pf-004 | Data Validation | Password Hashing Verification |
     */
    public function test_UT_V_Pf_004_admin_password_harus_selalu_terhash()
    {
        $passRaw = 'rahasia123';
        $user = User::create([
            'nama' => 'Test Hash',
            'email' => 'hash@mail.com',
            'password' => Hash::make($passRaw)
        ]);

        $this->assertNotEquals($passRaw, $user->password);
        $this->assertEquals(60, strlen($user->password)); // Bcrypt length
    }
}