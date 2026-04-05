<?php

namespace Tests\Unit\Models;

use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\RasHewan;
use App\Models\JenisHewan;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Database\QueryException;

/**
 * UNIT TEST — Fitur Create Pet (Hewan Peliharaan)
 *
 * SKENARIO PENGUJIAN:
 * [Nama Test] [Functionality/Usability] [Object: Pet] [Function: Create via Reflection]
 *
 */
class DBCreatePetUnitTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $rolePemilik;
    private $jenisHewan;
    private $rasHewan;
    private $pemilik;

    public function setUp(): void
    {
        parent::setUp();

        // Setup Role
        $this->rolePemilik = Role::create([
            'idrole' => 5,
            'nama_role' => 'Pemilik'
        ]);

        // Setup User
        $this->user = User::create([
            'iduser' => 1234,
            'nama' => 'Test Pemilik',
            'email' => 'pemilik@example.com',
            'password' => Hash::make('password')
        ]);

        DB::table('role_user')->insert([
            'iduser' => $this->user->iduser,
            'idrole' => $this->rolePemilik->idrole,
            'status' => 1
        ]);

        // Setup Jenis Hewan
        $this->jenisHewan = JenisHewan::create([
            'idjenis_hewan' => 1,
            'nama_jenis_hewan' => 'Kucing'
        ]);

        // Setup Ras Hewan
        $this->rasHewan = RasHewan::create([
            'idras_hewan' => 1,
            'nama_ras' => 'Persia',
            'idjenis_hewan' => $this->jenisHewan->idjenis_hewan
        ]);

        // Setup Pemilik
        $this->pemilik = Pemilik::create([
            'idpemilik' => 1234,
            'iduser' => $this->user->iduser,
            'no_wa' => '081234567890',
            'alamat' => 'Surabaya'
        ]);
    }

    // ========== POSITIVE TEST CASES ==========

    public function test_functionality_pet_create_via_reflection_success()
    {
        $pet = Pet::create([
            'nama' => 'Muezza',
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => 1234,
            'tanggal_lahir' => '2024-01-01'
        ]);

        $this->assertNotNull($pet->idpet);
        $this->assertEquals('Muezza', $pet->nama);
        $this->assertEquals('F', $pet->jenis_kelamin);

        $this->assertDatabaseHas('pet', [
            'nama' => 'Muezza',
            'idpemilik' => 1234
        ]);
    }

    public function test_functionality_pet_create_nullable_tanggal_lahir_via_reflection()
    {
        $pet = Pet::create([
            'nama' => 'Muezza',
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => 1234,
            'tanggal_lahir' => null
        ]);

        $this->assertNull($pet->tanggal_lahir);

        $this->assertDatabaseHas('pet', [
            'idpet' => $pet->idpet,
            'tanggal_lahir' => null
        ]);
    }

    public function test_functionality_pet_create_nullable_warna_tanda_via_reflection()
    {
        $pet = Pet::create([
            'nama' => 'Muezza',
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => 1234,
            'warna_tanda' => null
        ]);

        $this->assertNull($pet->warna_tanda);

        $this->assertDatabaseHas('pet', [
            'idpet' => $pet->idpet,
            'warna_tanda' => null
        ]);
    }

    // ========== NEGATIVE TEST CASES ==========

    public function test_usability_pet_create_fail_nama_null_via_reflection()
    {
        $error = false;

        try {
            Pet::create([
                'nama' => null,
                'jenis_kelamin' => 'F',
                'idras_hewan' => 1,
                'idpemilik' => 1234
            ]);
        } catch (QueryException $e) {
            $error = true;
        }

        $this->assertTrue($error, 'Harus gagal jika nama null');

        $this->assertDatabaseMissing('pet', [
            'jenis_kelamin' => 'F',
            'idpemilik' => 1234
        ]);
    }

    public function test_usability_pet_create_fail_jenis_kelamin_null_via_reflection()
    {
        $error = false;

        try {
            Pet::create([
                'nama' => 'Muezza',
                'jenis_kelamin' => null,
                'idras_hewan' => 1,
                'idpemilik' => 1234
            ]);
        } catch (QueryException $e) {
            $error = true;
        }

        $this->assertTrue($error, 'Harus gagal jika jenis_kelamin null');

        $this->assertDatabaseMissing('pet', [
            'nama' => 'Muezza'
        ]);
    }

    public function test_usability_pet_create_fail_idras_null_via_reflection()
    {
        $error = false;

        try {
            Pet::create([
                'nama' => 'Muezza',
                'jenis_kelamin' => 'F',
                'idras_hewan' => null,
                'idpemilik' => 1234
            ]);
        } catch (QueryException $e) {
            $error = true;
        }

        $this->assertTrue($error, 'Harus gagal jika idras_hewan null');

        $this->assertDatabaseMissing('pet', [
            'nama' => 'Muezza'
        ]);
    }

    public function test_usability_pet_create_fail_idpemilik_null_via_reflection()
    {
        $error = false;

        try {
            Pet::create([
                'nama' => 'Muezza',
                'jenis_kelamin' => 'F',
                'idras_hewan' => 1,
                'idpemilik' => null
            ]);
        } catch (QueryException $e) {
            $error = true;
        }

        $this->assertTrue($error, 'Harus gagal jika idpemilik null');

        $this->assertDatabaseMissing('pet', [
            'nama' => 'Muezza'
        ]);
    }

    public function test_usability_pet_create_fail_jenis_kelamin_invalid_via_reflection()
    {
        $error = false;

        try {
            Pet::create([
                'nama' => 'Muezza',
                'jenis_kelamin' => 'X',
                'idras_hewan' => 1,
                'idpemilik' => 1234
            ]);
        } catch (QueryException $e) {
            $error = true;
        }

        $this->assertTrue($error, 'Harus gagal jika jenis_kelamin tidak valid');

        $this->assertDatabaseMissing('pet', [
            'nama' => 'Muezza',
            'jenis_kelamin' => 'X'
        ]);
    }
}