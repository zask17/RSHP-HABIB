<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Pet;
use App\Models\RekamMedis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminRekamMedisTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $roleAdmin;
    protected $dokterRoleUserId;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role Administrator dan User Admin
        $this->roleAdmin = Role::create(['nama_role' => 'Administrator']);
        $this->admin = User::factory()->create();

        DB::table('role_user')->insert([
            'iduser' => $this->admin->iduser,
            'idrole' => $this->roleAdmin->idrole,
            'status' => 1
        ]);

        // 2. Setup Role Dokter
        $roleDokter = Role::create(['nama_role' => 'Dokter']);
        $dokterUser = User::factory()->create();
        $this->dokterRoleUserId = DB::table('role_user')->insertGetId([
            'iduser' => $dokterUser->iduser,
            'idrole' => $roleDokter->idrole,
            'status' => 1
        ]);

        // 3. Setup Master Data Tindakan
        $idKat = DB::table('kategori')->insertGetId(['nama_kategori' => 'Umum']);
        $idKatKlinis = DB::table('kategori_klinis')->insertGetId(['nama_kategori_klinis' => 'Klinis']);
        
        DB::table('kode_tindakan_terapi')->insert([
            'idkode_tindakan_terapi' => 1,
            'kode' => 'TDK-TEST',
            'deskripsi_tindakan_terapi' => 'Pemeriksaan Rutin Testing',
            'idkategori' => $idKat,
            'idkategori_klinis' => $idKatKlinis
        ]);
    }

    /** #AdminRM001: Admin dapat melihat daftar rekam medis */
    public function test_admin_dapat_melihat_daftar_rekam_medis()
    {
        // PENTING: Mengabaikan error session_start() saat render view
        $this->withoutExceptionHandling();

        $response = $this->actingAs($this->admin)
            ->get(route('data.rekam-medis.index'));

        $response->assertStatus(200);
    }

    /** #AdminRM002: Admin dapat mengakses form edit data utama */
    public function test_admin_dapat_mengakses_form_edit_data_utama()
    {
        $this->withoutExceptionHandling(); // Tambahkan ini di setiap test yang merender view

        $pet = Pet::factory()->create();
        $rm = RekamMedis::factory()->create([
            'idpet' => $pet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('data.rekam-medis.edit-data', $rm->idrekam_medis));

        $response->assertStatus(200);
    }

    /** #AdminRM003: Admin berhasil memperbarui data utama rekam medis */
    public function test_admin_berhasil_update_data_utama()
    {
        $this->withoutExceptionHandling();
        
        $pet = Pet::factory()->create();
        $rm = RekamMedis::factory()->create([
            'idpet' => $pet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId
        ]);

        $newPet = Pet::factory()->create();
        $payload = [
            'anamnesa' => 'Anamnesa diubah Admin',
            'temuan_klinis' => 'Temuan diubah Admin',
            'diagnosa' => 'Diagnosa Baru Admin',
            'idpet' => $newPet->idpet,
            'dokter_pemeriksa' => $this->dokterRoleUserId,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('data.rekam-medis.update-data', $rm->idrekam_medis), $payload);

        $response->assertRedirect(route('data.rekam-medis.index'));
    }

    /** #AdminRM004: Admin dapat mengakses form edit detail tindakan */
    public function test_admin_dapat_mengakses_form_edit_detail_tindakan()
    {
        $this->withoutExceptionHandling();

        $rm = RekamMedis::factory()->create(['dokter_pemeriksa' => $this->dokterRoleUserId]);

        $response = $this->actingAs($this->admin)
            ->get(route('data.rekam-medis.edit-detail', $rm->idrekam_medis));

        $response->assertStatus(200);
    }

    /** #AdminRM005: Admin berhasil memperbarui detail tindakan rekam medis */
    public function test_admin_berhasil_update_detail_tindakan()
    {
        $this->withoutExceptionHandling();

        $rm = RekamMedis::factory()->create(['dokter_pemeriksa' => $this->dokterRoleUserId]);

        $payload = [
            'detail_tindakan' => [
                [
                    'idkode_tindakan_terapi' => 1,
                    'detail' => 'Tindakan medis oleh Admin'
                ]
            ]
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('data.rekam-medis.update-detail', $rm->idrekam_medis), $payload);

        $response->assertRedirect(route('data.rekam-medis.index'));
    }

    /** #AdminRM006: Admin dapat melihat detail rekam medis (Show) */
    public function test_admin_dapat_melihat_show_rekam_medis()
    {
        $this->withoutExceptionHandling();

        $rm = RekamMedis::factory()->create(['dokter_pemeriksa' => $this->dokterRoleUserId]);

        $response = $this->actingAs($this->admin)
            ->get(route('data.rekam-medis.show', $rm->idrekam_medis));

        $response->assertStatus(200);
    }
}