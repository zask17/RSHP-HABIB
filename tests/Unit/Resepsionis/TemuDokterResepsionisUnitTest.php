<?php

namespace Tests\Unit\Resepsionis;

use App\Models\TemuDokter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * UNIT TEST — Temu Dokter (Resepsionis Role)
 * Resepsionis memiliki akses penuh untuk CRUD dan validation
 * 
 * SKENARIO PENGUJIAN FORMAT:
 * [Test Code] | [Test Type] | [Object Being Tested] | [Function]
 */
class TemuDokterResepsionisUnitTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Aktifkan foreign key check untuk konsistensi data
        DB::statement('PRAGMA foreign_keys = ON');

        // 2. Buat data 'role' (Kakek 1)
        DB::table('role')->insert([
            'idrole' => 4,
            'nama_role' => 'Resepsionis'
        ]);

        // 3. Buat data 'user' (Kakek 2)
        DB::table('user')->insert([
            'iduser' => 6,
            'nama' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
        ]);

        // 4. Buat data 'role_user' (Induk langsung temu_dokter)
        // Data ini yang akan dirujuk oleh 'idrole_user' => 1 di test
        DB::table('role_user')->insert([
            'idrole_user' => 1,
            'iduser' => 6,
            'idrole' => 4,
            'status' => 1
        ]);
    }
    // protected function setUp(): void
    // {
    //     parent::setUp();
    //     \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');
    // }

    // ============ FUNCTIONALITY TESTS (RESEPSIONIS) ============

    /**
     * UT-F-TD-001 | Functionality | Data tersimpan saat factory create |
     * Memastikan appointment dapat dibuat dengan factory dan semua field terisi
     */
    public function test_UT_F_TD_001_resepsionis_data_harus_tersimpan_saat_factory_create()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $this->assertNotNull($temu->idreservasi_dokter);
        $this->assertNotNull($temu->idrole_user);
        $this->assertNotNull($temu->waktu_daftar);
        $this->assertNotNull($temu->no_urut);
        $this->assertNotNull($temu->status);
    }

    /**
     * UT-F-TD-002 | Functionality | Data tersimpan dengan status Menunggu |
     * Memastikan appointment dibuat dengan status awal Menunggu
     */
    public function test_UT_F_TD_002_resepsionis_data_harus_tersimpan_dengan_status_menunggu()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $this->assertEquals(TemuDokter::STATUS_MENUNGGU, $temu->status);
        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temu->idreservasi_dokter,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
    }

    /**
     * UT-F-TD-003 | Functionality | Data tersimpan dengan status Selesai |
     * Memastikan appointment dapat dibuat dengan status Selesai
     */
    public function test_UT_F_TD_003_resepsionis_data_harus_tersimpan_dengan_status_selesai()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_SELESAI,
        ]);

        $this->assertEquals(TemuDokter::STATUS_SELESAI, $temu->status);
        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temu->idreservasi_dokter,
            'status' => TemuDokter::STATUS_SELESAI,
        ]);
    }

    /**
     * UT-F-TD-004 | Functionality | Data tersimpan dengan status Batal |
     * Memastikan appointment dapat dibuat dengan status Batal
     */
    public function test_UT_F_TD_004_resepsionis_data_harus_tersimpan_dengan_status_batal()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_BATAL,
        ]);

        $this->assertEquals(TemuDokter::STATUS_BATAL, $temu->status);
    }

    /**
     * UT-F-TD-005 | Functionality | Data dapat diambil berdasarkan ID |
     * Memastikan appointment dapat diambil dari database dengan find()
     */
    public function test_UT_F_TD_005_resepsionis_data_harus_dapat_diambil_berdasarkan_id()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        $retrieved = TemuDokter::find($temu->idreservasi_dokter);

        $this->assertNotNull($retrieved);
        $this->assertEquals($temu->idreservasi_dokter, $retrieved->idreservasi_dokter);
    }

    /**
     * UT-F-TD-006 | Functionality | Data dapat diambil semua appointment |
     * Memastikan dapat mengambil semua appointment dari database
     */
    public function test_UT_F_TD_006_resepsionis_data_harus_dapat_diambil_semua_appointment()
    {
        for ($i = 0; $i < 5; $i++) {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => $i + 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);
        }

        $all = TemuDokter::all();

        $this->assertCount(5, $all);
    }

    /**
     * UT-F-TD-007 | Functionality | Status dapat diubah dari Menunggu ke Selesai |
     * Memastikan status appointment dapat di-update (ADMIN ONLY)
     */
    public function test_UT_F_TD_007_resepsionis_status_harus_dapat_diubah_dari_menunggu_ke_selesai()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $temu->update(['status' => TemuDokter::STATUS_SELESAI]);

        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temu->idreservasi_dokter,
            'status' => TemuDokter::STATUS_SELESAI,
        ]);
    }

    /**
     * UT-F-TD-008 | Functionality | No urut dapat diubah |
     * Memastikan nomor urut appointment dapat di-update (ADMIN ONLY)
     */
    public function test_UT_F_TD_008_resepsionis_no_urut_harus_dapat_diubah()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $temu->update(['no_urut' => 50]);

        $this->assertEquals(50, $temu->fresh()->no_urut);
    }

    /**
     * UT-F-TD-009 | Functionality | Waktu daftar dapat diubah |
     * Memastikan waktu appointment dapat di-update (ADMIN ONLY)
     */
    public function test_UT_F_TD_009_resepsionis_waktu_daftar_harus_dapat_diubah()
    {
        $time1 = Carbon::now();
        $time2 = Carbon::now()->addDays(5);

        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => $time1,
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        $temu->update(['waktu_daftar' => $time2]);

        $this->assertEquals($time2->format('Y-m-d'), $temu->fresh()->waktu_daftar->format('Y-m-d'));
    }

    /**
     * UT-F-TD-010 | Functionality | Multiple field dapat diubah sekaligus |
     * Memastikan multiple fields dapat di-update dalam satu operasi (ADMIN ONLY)
     */
    public function test_UT_F_TD_010_resepsionis_multiple_field_harus_dapat_diubah_sekaligus()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $newTime = Carbon::now()->addDays(3);
        $temu->update([
            'no_urut' => 25,
            'status' => TemuDokter::STATUS_SELESAI,
            'waktu_daftar' => $newTime,
        ]);

        $fresh = $temu->fresh();
        $this->assertEquals(25, $fresh->no_urut);
        $this->assertEquals(TemuDokter::STATUS_SELESAI, $fresh->status);
    }

    /**
     * UT-F-TD-011 | Functionality | Data dapat dihapus dari database |
     * Memastikan appointment dapat dihapus (ADMIN ONLY)
     */
    public function test_UT_F_TD_011_resepsionis_data_harus_dapat_dihapus_dari_database()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        $id = $temu->idreservasi_dokter;

        $temu->delete();

        $this->assertNull(TemuDokter::find($id));
    }

    /**
     * UT-F-TD-012 | Functionality | Multiple data dapat dihapus sekaligus |
     * Memastikan multiple appointments dapat dihapus bersamaan (ADMIN ONLY)
     */
    public function test_UT_F_TD_012_resepsionis_multiple_data_harus_dapat_dihapus_sekaligus()
    {
        $temu1 = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        $temu2 = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 2,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        $temu3 = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 3,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        TemuDokter::whereIn('idreservasi_dokter', [
            $temu1->idreservasi_dokter,
            $temu2->idreservasi_dokter,
            $temu3->idreservasi_dokter,
        ])->delete();

        $this->assertCount(0, TemuDokter::all());
    }

    /**
     * UT-F-TD-013 | Functionality | Scope active mengembalikan non-cancelled |
     * Memastikan scope active() hanya mengembalikan non-cancelled appointments
     */
    public function test_UT_F_TD_013_resepsionis_scope_active_harus_mengembalikan_non_cancelled()
    {
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 2,
            'status' => TemuDokter::STATUS_SELESAI,
        ]);
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 3,
            'status' => TemuDokter::STATUS_BATAL,
        ]);

        $active = TemuDokter::active()->get();

        $this->assertCount(2, $active);
        $this->assertTrue($active->every(fn($t) => $t->status !== TemuDokter::STATUS_BATAL));
    }

    /**
     * UT-F-TD-014 | Functionality | Scope pending mengembalikan status Menunggu |
     * Memastikan scope pending() hanya mengembalikan status Menunggu
     */
    public function test_UT_F_TD_014_resepsionis_scope_pending_harus_mengembalikan_status_menunggu()
    {
        for ($i = 0; $i < 3; $i++) {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => $i + 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => $i + 4,
                'status' => TemuDokter::STATUS_SELESAI,
            ]);
        }
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 6,
            'status' => TemuDokter::STATUS_BATAL,
        ]);

        $pending = TemuDokter::pending()->get();

        $this->assertCount(3, $pending);
        $this->assertTrue($pending->every(fn($t) => $t->status === TemuDokter::STATUS_MENUNGGU));
    }

    /**
     * UT-F-TD-015 | Functionality | Status 0 ditampilkan sebagai Menunggu |
     * Memastikan status code di-convert ke readable text
     */
    public function test_UT_F_TD_015_resepsionis_status_0_harus_ditampilkan_sebagai_menunggu()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $this->assertEquals('Menunggu', $temu->getStatusTextAttribute());
    }

    /**
     * UT-F-TD-016 | Functionality | Status 1 ditampilkan sebagai Selesai |
     * Memastikan status code di-convert ke readable text
     */
    public function test_UT_F_TD_016_resepsionis_status_1_harus_ditampilkan_sebagai_selesai()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_SELESAI,
        ]);

        $this->assertEquals('Selesai', $temu->getStatusTextAttribute());
    }

    /**
     * UT-F-TD-017 | Functionality | Status 2 ditampilkan sebagai Batal |
     * Memastikan status code di-convert ke readable text
     */
    public function test_UT_F_TD_017_resepsionis_status_2_harus_ditampilkan_sebagai_batal()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_BATAL,
        ]);

        $this->assertEquals('Batal', $temu->getStatusTextAttribute());
    }

    /**
     * UT-F-TD-018 | Functionality | Data dapat diurutkan berdasarkan waktu |
     * Memastikan appointment dapat diurutkan ascending berdasarkan waktu
     */
    public function test_UT_F_TD_018_resepsionis_data_harus_dapat_diurutkan_berdasarkan_waktu()
    {
        $time1 = Carbon::now();
        $time2 = Carbon::now()->addDay();
        $time3 = Carbon::now()->addDays(2);

        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => $time3,
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => $time1,
            'no_urut' => 2,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => $time2,
            'no_urut' => 3,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $sorted = TemuDokter::orderBy('waktu_daftar', 'asc')->get();

        $this->assertTrue($sorted[0]->waktu_daftar < $sorted[1]->waktu_daftar);
        $this->assertTrue($sorted[1]->waktu_daftar < $sorted[2]->waktu_daftar);
    }

    /**
     * UT-F-TD-019 | Functionality | Data dapat diurutkan berdasarkan no_urut |
     * Memastikan appointment dapat diurutkan ascending berdasarkan nomor urut
     */
    public function test_UT_F_TD_019_resepsionis_data_harus_dapat_diurutkan_berdasarkan_no_urut()
    {
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 5,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 3,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $sorted = TemuDokter::orderBy('no_urut', 'asc')->get();

        $this->assertEquals(1, $sorted[0]->no_urut);
        $this->assertEquals(3, $sorted[1]->no_urut);
        $this->assertEquals(5, $sorted[2]->no_urut);
    }

    /**
     * UT-F-TD-020 | Functionality | Data dapat difilter berdasarkan status |
     * Memastikan appointment dapat difilter berdasarkan status tertentu
     */
    public function test_UT_F_TD_020_resepsionis_data_harus_dapat_difilter_berdasarkan_status()
    {
        for ($i = 0; $i < 3; $i++) {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => $i + 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => $i + 4,
                'status' => TemuDokter::STATUS_SELESAI,
            ]);
        }
        TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 6,
            'status' => TemuDokter::STATUS_BATAL,
        ]);

        $menunggu = TemuDokter::where('status', TemuDokter::STATUS_MENUNGGU)->count();
        $selesai = TemuDokter::where('status', TemuDokter::STATUS_SELESAI)->count();
        $batal = TemuDokter::where('status', TemuDokter::STATUS_BATAL)->count();

        $this->assertEquals(3, $menunggu);
        $this->assertEquals(2, $selesai);
        $this->assertEquals(1, $batal);
    }

    /**
     * UT-F-TD-021 | Functionality | Total data dapat dihitung |
     * Memastikan dapat menghitung total appointment
     */
    public function test_UT_F_TD_021_resepsionis_total_data_harus_dapat_dihitung()
    {
        for ($i = 0; $i < 10; $i++) {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => $i + 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);
        }

        $count = TemuDokter::count();

        $this->assertEquals(10, $count);
    }

    /**
     * UT-F-TD-022 | Functionality | Status constant bernilai benar |
     * Memastikan status constants memiliki nilai yang benar
     */
    public function test_UT_F_TD_022_resepsionis_status_constant_harus_bernilai_benar()
    {
        $this->assertEquals('0', TemuDokter::STATUS_MENUNGGU);
        $this->assertEquals('1', TemuDokter::STATUS_SELESAI);
        $this->assertEquals('2', TemuDokter::STATUS_BATAL);
    }

    /**
     * UT-F-TD-023 | Functionality | Primary key adalah idreservasi_dokter |
     * Memastikan primary key model adalah idreservasi_dokter
     */
    public function test_UT_F_TD_023_resepsionis_primary_key_harus_adalah_idreservasi_dokter()
    {
        $temu = new TemuDokter();

        $this->assertEquals('idreservasi_dokter', $temu->getKeyName());
    }

    /**
     * UT-F-TD-024 | Functionality | Table name adalah temu_dokter |
     * Memastikan model terhubung ke tabel temu_dokter
     */
    public function test_UT_F_TD_024_resepsionis_table_name_harus_adalah_temu_dokter()
    {
        $temu = new TemuDokter();

        $this->assertEquals('temu_dokter', $temu->getTable());
    }

    /**
     * UT-F-TD-025 | Functionality | Fillable attributes terdapat semua field |
     * Memastikan semua fields yang diperlukan adalah mass assignable
     */
    public function test_UT_F_TD_025_resepsionis_fillable_attributes_harus_terdapat_semua_field()
    {
        $temu = new TemuDokter();
        $fillable = $temu->getFillable();

        $this->assertContains('no_urut', $fillable);
        $this->assertContains('waktu_daftar', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('idrole_user', $fillable);
    }

    /**
     * UT-F-TD-026 | Functionality | Timestamps tidak diaktifkan |
     * Memastikan model tidak auto-manage created_at/updated_at
     */
    public function test_UT_F_TD_026_resepsionis_timestamps_harus_tidak_diaktifkan()
    {
        $temu = new TemuDokter();

        $this->assertFalse($temu->timestamps);
    }

    /**
     * UT-F-TD-027 | Functionality | Status di-cast sebagai string |
     * Memastikan status di-cast sebagai string saat retrieval
     */
    public function test_UT_F_TD_027_resepsionis_status_harus_di_cast_sebagai_string()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => '0',
        ]);
        $fresh = $temu->fresh();

        $this->assertIsString($fresh->status);
        $this->assertEquals('0', $fresh->status);
    }

    /**
     * UT-F-TD-028 | Functionality | Waktu daftar di-cast sebagai datetime |
     * Memastikan waktu_daftar di-cast sebagai Carbon instance
     */
    public function test_UT_F_TD_028_resepsionis_waktu_daftar_harus_di_cast_sebagai_datetime()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        $fresh = $temu->fresh();

        $this->assertInstanceOf(Carbon::class, $fresh->waktu_daftar);
    }

    /**
     * UT-F-TD-029 | Functionality | Bulk update status dapat dilakukan |
     * Memastikan dapat bulk update status multiple appointments (ADMIN ONLY)
     */
    public function test_UT_F_TD_029_resepsionis_bulk_update_status_harus_dapat_dilakukan()
    {
        for ($i = 0; $i < 5; $i++) {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => $i + 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);
        }

        TemuDokter::where('status', TemuDokter::STATUS_MENUNGGU)
            ->update(['status' => TemuDokter::STATUS_SELESAI]);

        $this->assertCount(5, TemuDokter::where('status', TemuDokter::STATUS_SELESAI)->get());
    }

    /**
     * UT-F-TD-030 | Functionality | Data integritas terjaga saat simpan |
     * Memastikan data yang disimpan di database sesuai dengan input
     */
    public function test_UT_F_TD_030_resepsionis_data_integritas_harus_terjaga_saat_simpan()
    {
        $data = [
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 42,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ];

        $temu = TemuDokter::create($data);

        $this->assertDatabaseHas('temu_dokter', [
            'idrole_user' => 1,
            'no_urut' => 42,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
    }

    // ============ DATA VALIDATION TESTS (RESEPSIONIS) ============

    /**
     * UT-V_TD-001 | Data Validation | Harus gagal jika idrole_user kosong |
     * Memastikan appointment tidak dapat dibuat tanpa dokter reference
     */
    public function test_UT_V_TD_001_resepsionis_harus_gagal_jika_idrole_user_kosong()
    {
        try {
            TemuDokter::create([
                'waktu_daftar' => Carbon::now(),
                'no_urut' => 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena idrole_user kosong');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-002 | Data Validation | Harus gagal jika waktu_daftar kosong |
     * Memastikan appointment tidak dapat dibuat tanpa waktu registrasi
     */
    public function test_UT_V_TD_002_resepsionis_harus_gagal_jika_waktu_daftar_kosong()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'no_urut' => 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena waktu_daftar kosong');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-003 | Data Validation | Harus gagal jika no_urut kosong |
     * Memastikan appointment tidak dapat dibuat tanpa nomor urut
     */
    public function test_UT_V_TD_003_resepsionis_harus_gagal_jika_no_urut_kosong()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena no_urut kosong');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-004 | Data Validation | Harus gagal jika status kosong |
     * Memastikan appointment tidak dapat dibuat tanpa status
     */
    public function test_UT_V_TD_004_resepsionis_harus_gagal_jika_status_kosong()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => 1,
            ]);

            $this->fail('Seharusnya gagal karena status kosong');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-005 | Data Validation | Harus gagal jika idrole_user null |
     * Memastikan appointment tidak dapat dibuat dengan idrole_user null
     */
    public function test_UT_V_TD_005_resepsionis_harus_gagal_jika_idrole_user_null()
    {
        try {
            TemuDokter::create([
                'idrole_user' => null,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena idrole_user null');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-006 | Data Validation | Harus gagal jika waktu_daftar null |
     * Memastikan appointment tidak dapat dibuat dengan waktu_daftar null
     */
    public function test_UT_V_TD_006_resepsionis_harus_gagal_jika_waktu_daftar_null()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => null,
                'no_urut' => 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena waktu_daftar null');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-007 | Data Validation | Harus gagal jika no_urut null |
     * Memastikan appointment tidak dapat dibuat dengan no_urut null
     */
    public function test_UT_V_TD_007_resepsionis_harus_gagal_jika_no_urut_null()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => null,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena no_urut null');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-008 | Data Validation | Harus gagal jika status null |
     * Memastikan appointment tidak dapat dibuat dengan status null
     */
    public function test_UT_V_TD_008_resepsionis_harus_gagal_jika_status_null()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => 1,
                'status' => null,
            ]);

            $this->fail('Seharusnya gagal karena status null');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-009 | Data Validation | Harus gagal jika semua field kosong |
     * Memastikan appointment tidak dapat dibuat dengan semua field kosong
     */
    public function test_UT_V_TD_009_resepsionis_harus_gagal_jika_semua_field_kosong()
    {
        try {
            TemuDokter::create([]);

            $this->fail('Seharusnya gagal karena semua field kosong');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-010 | Data Validation | Harus gagal jika hanya 1 field terisi |
     * Memastikan appointment tidak dapat dibuat dengan hanya 1 field
     */
    public function test_UT_V_TD_010_resepsionis_harus_gagal_jika_hanya_1_field_terisi()
    {
        try {
            TemuDokter::create(['idrole_user' => 1]);

            $this->fail('Seharusnya gagal karena hanya 1 field');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-011 | Data Validation | Harus gagal jika hanya 2 field terisi |
     * Memastikan appointment tidak dapat dibuat dengan hanya 2 field
     */
    public function test_UT_V_TD_011_resepsionis_harus_gagal_jika_hanya_2_field_terisi()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
            ]);

            $this->fail('Seharusnya gagal karena hanya 2 field');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-012 | Data Validation | Harus gagal jika hanya 3 field terisi |
     * Memastikan appointment tidak dapat dibuat dengan hanya 3 field
     */
    public function test_UT_V_TD_012_resepsionis_harus_gagal_jika_hanya_3_field_terisi()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => 1,
            ]);

            $this->fail('Seharusnya gagal karena hanya 3 field');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-013 | Data Validation | Harus gagal jika waktu format invalid |
     * Memastikan appointment tidak dapat dibuat dengan format waktu tidak valid
     */
    public function test_UT_V_TD_013_resepsionis_harus_gagal_jika_waktu_format_invalid()
    {
        try {
            TemuDokter::create([
                'idrole_user' => 1,
                'waktu_daftar' => 'bukan-datetime-format',
                'no_urut' => 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena format waktu invalid');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-014 | Data Validation | Harus gagal jika update idrole_user ke null |
     * Memastikan update appointment tidak bisa set idrole_user null (ADMIN ONLY CONSTRAINT)
     */
    public function test_UT_V_TD_014_resepsionis_harus_gagal_jika_update_idrole_user_ke_null()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        try {
            $temu->update(['idrole_user' => null]);
            $this->fail('Seharusnya gagal karena idrole_user diset null');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-015 | Data Validation | Harus gagal jika update waktu ke null |
     * Memastikan update tidak bisa set waktu_daftar null (ADMIN ONLY CONSTRAINT)
     */
    public function test_UT_V_TD_015_resepsionis_harus_gagal_jika_update_waktu_ke_null()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        try {
            $temu->update(['waktu_daftar' => null]);
            $this->fail('Seharusnya gagal karena waktu_daftar diset null');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-016 | Data Validation | Harus gagal jika update no_urut ke null |
     * Memastikan update tidak bisa set no_urut null (ADMIN ONLY CONSTRAINT)
     */
    public function test_UT_V_TD_016_resepsionis_harus_gagal_jika_update_no_urut_ke_null()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        try {
            $temu->update(['no_urut' => null]);
            $this->fail('Seharusnya gagal karena no_urut diset null');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-017 | Data Validation | Harus gagal jika update status ke null |
     * Memastikan update tidak bisa set status null (ADMIN ONLY CONSTRAINT)
     */
    public function test_UT_V_TD_017_resepsionis_harus_gagal_jika_update_status_ke_null()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        try {
            $temu->update(['status' => null]);
            $this->fail('Seharusnya gagal karena status diset null');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-018 | Data Validation | Harus gagal jika duplicate primary key |
     * Memastikan tidak bisa membuat 2 appointment dengan ID sama (ADMIN ONLY VIEW)
     */
    public function test_UT_V_TD_018_resepsionis_harus_gagal_jika_duplicate_primary_key()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        try {
            TemuDokter::create([
                'idreservasi_dokter' => $temu->idreservasi_dokter,
                'idrole_user' => 2,
                'waktu_daftar' => Carbon::now(),
                'no_urut' => 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena ID duplicate');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * UT-V_TD-019 | Data Validation | Harus SUKSES jika semua 4 field lengkap |
     * Memastikan appointment DAPAT dibuat ketika semua 4 field terisi lengkap
     */
    public function test_UT_V_TD_019_resepsionis_harus_sukses_jika_semua_4_field_lengkap()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $this->assertNotNull($temu->idreservasi_dokter);
        $this->assertDatabaseHas('temu_dokter', [
            'idrole_user' => 1,
            'no_urut' => 1,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
    }

    /**
     * UT-V_TD-020 | Data Validation | Harus SUKSES dengan factory override |
     * Memastikan dapat membuat appointment dengan custom values
     */
    public function test_UT_V_TD_020_resepsionis_harus_sukses_dengan_factory_override()
    {
        $temu = TemuDokter::create([
            'idrole_user' => 1,
            'waktu_daftar' => Carbon::now(),
            'no_urut' => 99,
            'status' => TemuDokter::STATUS_SELESAI,
        ]);

        $this->assertNotNull($temu->idreservasi_dokter);
        $this->assertEquals(99, $temu->no_urut);
        $this->assertEquals(TemuDokter::STATUS_SELESAI, $temu->status);
    }

    /**
     * UT-V_TD-021 | Data Validation | Harus gagal jika 2 field required kosong |
     * Memastikan tidak bisa buat appointment dengan 2 field required kosong
     */
    public function test_UT_V_TD_021_resepsionis_harus_gagal_jika_2_field_required_kosong()
    {
        try {
            TemuDokter::create([
                'no_urut' => 1,
                'status' => TemuDokter::STATUS_MENUNGGU,
            ]);

            $this->fail('Seharusnya gagal karena 2 field required kosong');
        } catch (QueryException $e) {
            $this->assertTrue(true);
        }
    }
}
