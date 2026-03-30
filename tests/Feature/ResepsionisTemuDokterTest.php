<?php

namespace Tests\Feature\TemuDokter;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResepsionisTemuDokterTest extends TestCase
{
    use RefreshDatabase;

    protected $resepsionisUser;
    protected $roleResepsionis;
    protected $dokterRoleUserId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleResepsionis = Role::create(['nama_role' => 'Resepsionis']);
        $this->resepsionisUser = User::factory()->create(['nama' => 'Resepsionis Test']);

        // Attach role Resepsionis
        DB::table('role_user')->insert([
            'iduser' => $this->resepsionisUser->iduser,
            'idrole' => $this->roleResepsionis->idrole,
            'status' => 1
        ]);

        // Buat dokter aktif untuk testing
        $dokterUser = User::factory()->create(['nama' => 'Dokter Test']);
        $this->dokterRoleUserId = DB::table('role_user')->insertGetId([
            'iduser' => $dokterUser->iduser,
            'idrole' => Role::create(['nama_role' => 'Dokter'])->idrole,
            'status' => 1
        ]);
    }

    /** #TemuDokterResep001: Resepsionis dapat melihat daftar temu dokter */
    public function test_resepsionis_dapat_melihat_daftar_temu_dokter()
    {
        \App\Models\TemuDokter::factory()->menunggu()->create([
            'idrole_user' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->get(route('data.temu-dokter.index'));

        $response->assertStatus(200);
        $response->assertViewIs('data.temu-dokter.index');
    }

    /** #TemuDokterResep002: Resepsionis dapat mengakses form create */
    public function test_resepsionis_dapat_mengakses_form_create()
    {
        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->get(route('data.temu-dokter.create'));

        $response->assertStatus(200);
        $response->assertViewIs('data.temu-dokter.create');
    }

    /** #TemuDokterResep003: Resepsionis berhasil menambah temu dokter */
    public function test_resepsionis_berhasil_menambah_temu_dokter()
    {
        $payload = [
            'idrole_user'  => $this->dokterRoleUserId,
            'waktu_daftar' => now()->addDay()->format('Y-m-d H:i:s'),
            'no_urut'      => 10,
        ];

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->post(route('data.temu-dokter.store'), $payload);

        $response->assertRedirect(route('data.temu-dokter.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('temu_dokter', [
            'idrole_user' => $this->dokterRoleUserId,
            'no_urut'     => 10,
        ]);
    }

    /** #TemuDokterResep004: Resepsionis berhasil update temu dokter */
    public function test_resepsionis_berhasil_update_temu_dokter()
    {
        $temu = \App\Models\TemuDokter::factory()->create([
            'idrole_user' => $this->dokterRoleUserId
        ]);

        $payload = [
            'idrole_user'  => $this->dokterRoleUserId,
            'waktu_daftar' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'no_urut'      => 15,
            'status'       => '1',
        ];

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->put(route('data.temu-dokter.update', $temu->idreservasi_dokter), $payload);

        $response->assertRedirect(route('data.temu-dokter.index'));
        $response->assertSessionHas('success');
    }

    /** #TemuDokterResep005: Resepsionis berhasil update status (AJAX) */
    public function test_resepsionis_berhasil_update_status()
    {
        $temu = \App\Models\TemuDokter::factory()->menunggu()->create([
            'idrole_user' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->post(route('data.temu-dokter.update-status', $temu->idreservasi_dokter), [
                'status' => '1'
            ]);

        $response->assertJson(['success' => true]);
    }

    /** #TemuDokterResep006: Resepsionis berhasil menghapus temu dokter */
    public function test_resepsionis_berhasil_menghapus_temu_dokter()
    {
        $temu = \App\Models\TemuDokter::factory()->create([
            'idrole_user' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->resepsionisUser)
            ->withSession(['user_role' => $this->roleResepsionis->idrole])
            ->delete(route('data.temu-dokter.destroy', $temu->idreservasi_dokter));

        $response->assertRedirect(route('data.temu-dokter.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('temu_dokter', ['idreservasi_dokter' => $temu->idreservasi_dokter]);
    }
}