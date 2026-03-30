<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\RekamMedis;
use App\Models\Pet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DokterRekamMedisTest extends TestCase
{
    use RefreshDatabase;

    protected $dokter;
    protected $idRoleUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role Dokter dan User Dokter
        $role = Role::create(['nama_role' => 'Dokter']);
        $this->dokter = User::factory()->create();
        
        // Simpan idrole_user untuk digunakan sebagai dokter_pemeriksa
        $this->idRoleUser = DB::table('role_user')->insertGetId([
            'iduser' => $this->dokter->iduser, 
            'idrole' => $role->idrole, 
            'status' => 1
        ]);

        // 2. Setup Master Data Tindakan (Wajib ada agar update-detail tidak error foreign key)
        $idKat = DB::table('kategori')->insertGetId(['nama_kategori' => 'Umum']);
        $idKatKlinis = DB::table('kategori_klinis')->insertGetId(['nama_kategori_klinis' => 'Klinis']);
        
        DB::table('kode_tindakan_terapi')->insert([
            'idkode_tindakan_terapi' => 1,
            'kode' => 'TDK-DOKTER',
            'deskripsi_tindakan_terapi' => 'Tindakan oleh Dokter Test',
            'idkategori' => $idKat,
            'idkategori_klinis' => $idKatKlinis
        ]);
    }

    /** #DokterRM001: Dokter dapat melihat daftar rekam medis */
    public function test_dokter_dapat_melihat_rekam_medis()
    {
        // Mengabaikan error session_start() di Blade
        $this->withoutExceptionHandling();

        $response = $this->actingAs($this->dokter)
            ->get(route('data.rekam-medis.index'));

        $response->assertStatus(200);
        $response->assertViewIs('data.rekam-medis.index');
    }

    /** #DokterRM002: Dokter dapat mengakses form edit detail tindakan */
    public function test_dokter_dapat_mengakses_form_edit_detail_tindakan()
    {
        $this->withoutExceptionHandling();

        // Buat rekam medis di mana dokter ini adalah pemeriksanya
        $rm = RekamMedis::factory()->create([
            'dokter_pemeriksa' => $this->idRoleUser
        ]);

        $response = $this->actingAs($this->dokter)
            ->get(route('data.rekam-medis.edit-detail', $rm->idrekam_medis));

        $response->assertStatus(200);
    }

    /** #DokterRM003: Dokter berhasil update detail tindakan rekam medis */
    public function test_dokter_berhasil_update_detail_tindakan()
    {
        $this->withoutExceptionHandling();

        $rm = RekamMedis::factory()->create([
            'dokter_pemeriksa' => $this->idRoleUser
        ]);
        
        // Payload sesuai dengan struktur Request di RekamMedisController@updateDetail
        $payload = [
            'detail_tindakan' => [
                [
                    'idkode_tindakan_terapi' => 1, 
                    'detail' => 'Pemberian Obat A oleh Dokter'
                ]
            ]
        ];

        $response = $this->actingAs($this->dokter)
            ->put(route('data.rekam-medis.update-detail', $rm->idrekam_medis), $payload);

        $response->assertRedirect(route('data.rekam-medis.index'));
        $response->assertSessionHas('success');

        // Pastikan data tersimpan di tabel detail
        $this->assertDatabaseHas('detail_rekam_medis', [
            'idrekam_medis' => $rm->idrekam_medis,
            'detail' => 'Pemberian Obat A oleh Dokter'
        ]);
    }

    /** #DokterRM004: Dokter dapat melihat detail rekam medis (Show) */
    public function test_dokter_dapat_melihat_show_rekam_medis()
    {
        $this->withoutExceptionHandling();

        $rm = RekamMedis::factory()->create([
            'dokter_pemeriksa' => $this->idRoleUser
        ]);

        $response = $this->actingAs($this->dokter)
            ->get(route('data.rekam-medis.show', $rm->idrekam_medis));

        $response->assertStatus(200);
    }

    /** #DokterRM005: Dokter tidak dapat mengedit rekam medis milik dokter lain */
    public function test_dokter_tidak_dapat_mengedit_rekam_medis_orang_lain()
    {
        // Buat dokter lain
        $dokterLain = User::factory()->create();
        $idRoleUserLain = DB::table('role_user')->insertGetId([
            'iduser' => $dokterLain->iduser,
            'idrole' => Role::where('nama_role', 'Dokter')->first()->idrole,
            'status' => 1
        ]);

        // Rekam medis milik dokter lain
        $rmLain = RekamMedis::factory()->create([
            'dokter_pemeriksa' => $idRoleUserLain
        ]);

        $response = $this->actingAs($this->dokter)
            ->get(route('data.rekam-medis.edit-detail', $rmLain->idrekam_medis));

        // Berdasarkan Controller, harusnya redirect dengan error
        $response->assertRedirect(route('data.rekam-medis.index'));
        $response->assertSessionHas('error');
    }
}