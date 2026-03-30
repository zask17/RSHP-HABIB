<?php

namespace Tests\Feature\TemuDokter;

use App\Models\Role;
use App\Models\User;
use App\Models\TemuDokter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminTemuDokterTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $roleAdmin;
    protected $dokterRoleUserId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role dan User Admin
        $this->roleAdmin = Role::create(['nama_role' => 'Administrator']);
        $this->adminUser = User::factory()->create(['nama' => 'Admin Test']);

        DB::table('role_user')->insert([
            'iduser' => $this->adminUser->iduser,
            'idrole' => $this->roleAdmin->idrole,
            'status' => 1
        ]);

        // 2. Buat dokter aktif untuk relasi Temu Dokter
        $dokterUser = User::factory()->create(['nama' => 'Dokter Test']);
        $this->dokterRoleUserId = DB::table('role_user')->insertGetId([
            'iduser' => $dokterUser->iduser,
            'idrole' => Role::create(['nama_role' => 'Dokter'])->idrole,
            'status' => 1
        ]);
    }

    /** #TemuDokterAdmin001: Admin dapat melihat daftar temu dokter */
    public function test_admin_dapat_melihat_daftar_temu_dokter()
    {
        TemuDokter::factory()->menunggu()->create([
            'idrole_user' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->get(route('data.temu-dokter.index'));

        $response->assertStatus(200);
        $response->assertViewIs('data.temu-dokter.index');
    }

    /** #TemuDokterAdmin002: Admin dapat mengakses form create */
    public function test_admin_dapat_mengakses_form_create()
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->get(route('data.temu-dokter.create'));

        $response->assertStatus(200);
        $response->assertViewIs('data.temu-dokter.create');
    }

    /** #TemuDokterAdmin003: Admin berhasil menambah temu dokter */
    public function test_admin_berhasil_menambah_temu_dokter()
    {
        $payload = [
            'idrole_user'  => $this->dokterRoleUserId,
            'waktu_daftar' => now()->addDay()->format('Y-m-d H:i:s'),
            'no_urut'      => 1,
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->post(route('data.temu-dokter.store'), $payload);

        $response->assertRedirect(route('data.temu-dokter.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('temu_dokter', [
            'idrole_user' => $this->dokterRoleUserId,
            'no_urut'     => 1,
        ]);
    }

    /** #TemuDokterAdmin004: Admin berhasil update temu dokter */
    public function test_admin_berhasil_update_temu_dokter()
    {
        $temu = TemuDokter::factory()->create([
            'idrole_user' => $this->dokterRoleUserId
        ]);

        $payload = [
            'idrole_user'  => $this->dokterRoleUserId,
            'waktu_daftar' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'no_urut'      => 99,
            'status'       => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->put(route('data.temu-dokter.update', $temu->idreservasi_dokter), $payload);

        $response->assertRedirect(route('data.temu-dokter.index'));
        $this->assertDatabaseHas('temu_dokter', ['no_urut' => 99]);
    }

    /** #TemuDokterAdmin005: Admin berhasil update status (AJAX) */
    public function test_admin_berhasil_update_status()
    {
        $temu = TemuDokter::factory()->menunggu()->create([
            'idrole_user' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->post(route('data.temu-dokter.update-status', $temu->idreservasi_dokter), [
                'status' => '1'
            ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temu->idreservasi_dokter,
            'status' => '1'
        ]);
    }

    /** #TemuDokterAdmin006: Admin berhasil menghapus temu dokter */
    public function test_admin_berhasil_menghapus_temu_dokter()
    {
        $temu = TemuDokter::factory()->create([
            'idrole_user' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['user_role' => $this->roleAdmin->idrole])
            ->delete(route('data.temu-dokter.destroy', $temu->idreservasi_dokter));

        $response->assertRedirect(route('data.temu-dokter.index'));
        $this->assertDatabaseMissing('temu_dokter', ['idreservasi_dokter' => $temu->idreservasi_dokter]);
    }
}