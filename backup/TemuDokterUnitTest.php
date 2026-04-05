<?php

namespace Tests\Unit;

use App\Models\TemuDokter;
use App\Models\RoleUser;
use App\Models\User;
use App\Models\Role;
use Database\Factories\TemuDokterFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * UNIT TEST — Fitur Temu Dokter (Cluster - Appointment Management)
 * Menguji logika bisnis untuk Appointment berdasarkan database RSHP.
 * 
 * SKENARIO PENGUJIAN FORMAT:
 * [Nama Test] | [Test Apa] | [Objek yang Ditest] | [Fungsi]
 */
class TemuDokterUnitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-01: test_create_temu_dokter_dengan_status_menunggu
     * Functionality: Pembuatan appointment baru
     * Object Being Tested: TemuDokter model dengan status Menunggu
     * Function: Memastikan data appointment dapat dibuat dengan status awal 'Menunggu' (0)
     */
    public function test_create_temu_dokter_dengan_status_menunggu()
    {
        $temuDokter = TemuDokter::factory()->menunggu()->create();

        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);
        
        $this->assertEquals(TemuDokter::STATUS_MENUNGGU, $temuDokter->status);
    }

    /**
     * TC-02: test_temu_dokter_status_berubah_dari_menunggu_ke_selesai
     * Functionality: Update status appointment
     * Object Being Tested: TemuDokter memiliki no_urut dan status transisi
     * Function: Memastikan status dapat di-update dari 'Menunggu' (0) ke 'Selesai' (1)
     */
    public function test_temu_dokter_status_berubah_dari_menunggu_ke_selesai()
    {
        $temuDokter = TemuDokter::factory()->menunggu()->create();
        $this->assertEquals(TemuDokter::STATUS_MENUNGGU, $temuDokter->status);

        $temuDokter->update(['status' => TemuDokter::STATUS_SELESAI]);

        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
            'status' => TemuDokter::STATUS_SELESAI,
        ]);
    }

    /**
     * TC-03: test_temu_dokter_status_berubah_dari_menunggu_ke_batal
     * Functionality: Cancel appointment
     * Object Being Tested: TemuDokter dengan status yang dapat di-batal
     * Function: Memastikan appointment dapat dibatalkan dengan mengubah status ke 'Batal' (2)
     */
    public function test_temu_dokter_status_berubah_dari_menunggu_ke_batal()
    {
        $temuDokter = TemuDokter::factory()->menunggu()->create();
        
        $temuDokter->update(['status' => TemuDokter::STATUS_BATAL]);

        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
            'status' => TemuDokter::STATUS_BATAL,
        ]);
    }

    /**
     * TC-04: test_get_status_text_untuk_menunggu
     * Functionality: Display status text
     * Object Being Tested: TemuDokter dengan attribute getStatusTextAttribute
     * Function: Memastikan getText attribute mengubah kode status '0' menjadi teks 'Menunggu'
     */
    public function test_get_status_text_untuk_menunggu()
    {
        $temuDokter = TemuDokter::factory()
            ->state(['status' => TemuDokter::STATUS_MENUNGGU])
            ->create();

        $this->assertEquals('Menunggu', $temuDokter->getStatusTextAttribute());
    }

    /**
     * TC-05: test_get_status_text_untuk_selesai
     * Functionality: Display status text
     * Object Being Tested: TemuDokter dengan status Selesai
     * Function: Memastikan getAttribute mengubah kode status '1' menjadi teks 'Selesai'
     */
    public function test_get_status_text_untuk_selesai()
    {
        $temuDokter = TemuDokter::factory()
            ->state(['status' => TemuDokter::STATUS_SELESAI])
            ->create();

        $this->assertEquals('Selesai', $temuDokter->getStatusTextAttribute());
    }

    /**
     * TC-06: test_get_status_text_untuk_batal
     * Functionality: Display status text
     * Object Being Tested: TemuDokter dengan status Batal
     * Function: Memastikan getAttribute mengubah kode status '2' menjadi teks 'Batal'
     */
    public function test_get_status_text_untuk_batal()
    {
        $temuDokter = TemuDokter::factory()
            ->state(['status' => TemuDokter::STATUS_BATAL])
            ->create();

        $this->assertEquals('Batal', $temuDokter->getStatusTextAttribute());
    }

    /**
     * TC-07: test_scope_active_mengembalikan_hanya_appointment_tidak_batal
     * Functionality: Query filtering
     * Object Being Tested: TemuDokter dengan scope active (excluding cancelled)
     * Function: Memastikan scope active() hanya mengembalikan appointment yang status != 'Batal'
     */
    public function test_scope_active_mengembalikan_hanya_appointment_tidak_batal()
    {
        // Create mixed statuses
        TemuDokter::factory()->menunggu()->create();
        TemuDokter::factory()->selesai()->create();
        TemuDokter::factory()->batal()->create();

        $activeAppointments = TemuDokter::active()->get();

        $this->assertCount(2, $activeAppointments);
        $this->assertTrue($activeAppointments->every(fn($appt) => $appt->status !== TemuDokter::STATUS_BATAL));
    }

    /**
     * TC-08: test_scope_pending_mengembalikan_hanya_status_menunggu
     * Functionality: Query filtering
     * Object Being Tested: TemuDokter dengan scope pending
     * Function: Memastikan scope pending() hanya mengembalikan appointment dengan status 'Menunggu' (0)
     */
    public function test_scope_pending_mengembalikan_hanya_status_menunggu()
    {
        TemuDokter::factory()->menunggu()->create();
        TemuDokter::factory()->menunggu()->create();
        TemuDokter::factory()->selesai()->create();
        TemuDokter::factory()->batal()->create();

        $pendingAppointments = TemuDokter::pending()->get();

        $this->assertCount(2, $pendingAppointments);
        $this->assertTrue($pendingAppointments->every(fn($appt) => $appt->status === TemuDokter::STATUS_MENUNGGU));
    }

    /**
     * TC-09: test_temu_dokter_memiliki_no_urut_valid
     * Functionality: Data validation
     * Object Being Tested: TemuDokter dengan no_urut antara 1-50
     * Function: Memastikan no_urut bernilai positif dan sesuai dengan urutan
     */
    public function test_temu_dokter_memiliki_no_urut_valid()
    {
        $temuDokter = TemuDokter::factory()->create(['no_urut' => 10]);

        $this->assertGreaterThan(0, $temuDokter->no_urut);
        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
            'no_urut' => 10,
        ]);
    }

    /**
     * TC-10: test_temu_dokter_memiliki_waktu_daftar_valid
     * Functionality: Data validation
     * Object Being Tested: TemuDokter dengan waktu_daftar berupa datetime
     * Function: Memastikan waktu_daftar ter-cast sebagai datetime object
     */
    public function test_temu_dokter_memiliki_waktu_daftar_valid()
    {
        $temuDokter = TemuDokter::factory()->create();

        $this->assertInstanceOf(Carbon::class, $temuDokter->waktu_daftar);
        $this->assertNotNull($temuDokter->waktu_daftar);
    }

    /**
     * TC-11: test_temu_dokter_fillable_attributes_valid
     * Functionality: Mass assignment protection
     * Object Being Tested: TemuDokter dengan fillable array
     * Function: Memastikan hanya attribute yang diizinkan dapat di-mass assign
     */
    public function test_temu_dokter_fillable_attributes_valid()
    {
        $fillable = TemuDokter::factory()->make()->getFillable();

        $this->assertContains('no_urut', $fillable);
        $this->assertContains('waktu_daftar', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('idrole_user', $fillable);
    }

    /**
     * TC-12: test_temu_dokter_primary_key_adalah_idreservasi_dokter
     * Functionality: Model configuration
     * Object Being Tested: TemuDokter primary key configuration
     * Function: Memastikan primary key model adalah 'idreservasi_dokter' bukan 'id'
     */
    public function test_temu_dokter_primary_key_adalah_idreservasi_dokter()
    {
        $temuDokter = TemuDokter::factory()->create();

        $this->assertEquals('idreservasi_dokter', $temuDokter->getKeyName());
        $this->assertNotNull($temuDokter->idreservasi_dokter);
    }

    /**
     * TC-13: test_temu_dokter_table_name_adalah_temu_dokter
     * Functionality: Model configuration
     * Object Being Tested: TemuDokter table name configuration
     * Function: Memastikan model terhubung ke tabel 'temu_dokter' yang benar
     */
    public function test_temu_dokter_table_name_adalah_temu_dokter()
    {
        $temuDokter = new TemuDokter();

        $this->assertEquals('temu_dokter', $temuDokter->getTable());
    }

    /**
     * TC-14: test_temu_dokter_timestamps_disabled
     * Functionality: Model configuration
     * Object Being Tested: TemuDokter timestamps setting
     * Function: Memastikan model tidak secara otomatis manage created_at/updated_at
     */
    public function test_temu_dokter_timestamps_disabled()
    {
        $temuDokter = new TemuDokter();

        $this->assertFalse($temuDokter->timestamps);
    }

    /**
     * TC-15: test_temu_dokter_status_cast_to_string
     * Functionality: Data type casting
     * Object Being Tested: TemuDokter dengan status cast
     * Function: Memastikan status di-cast sebagai string agar konsisten
     */
    public function test_temu_dokter_status_cast_to_string()
    {
        $temuDokter = TemuDokter::factory()
            ->state(['status' => 0])
            ->create();

        // Verify it's stored as string
        $fromDb = TemuDokter::find($temuDokter->idreservasi_dokter);
        $this->assertIsString($fromDb->status);
        $this->assertEquals('0', $fromDb->status);
    }

    /**
     * TC-16: test_temu_dokter_waktu_daftar_cast_to_datetime
     * Functionality: Data type casting
     * Object Being Tested: TemuDokter dengan waktu_daftar cast
     * Function: Memastikan waktu_daftar di-cast sebagai datetime
     */
    public function test_temu_dokter_waktu_daftar_cast_to_datetime()
    {
        $now = Carbon::now();
        $temuDokter = TemuDokter::factory()
            ->state(['waktu_daftar' => $now])
            ->create();

        $fromDb = TemuDokter::find($temuDokter->idreservasi_dokter);
        $this->assertInstanceOf(Carbon::class, $fromDb->waktu_daftar);
    }

    /**
     * TC-17: test_temu_dokter_dapat_update_semua_fillable_fields
     * Functionality: Update operation
     * Object Being Tested: TemuDokter dengan semua fillable fields
     * Function: Memastikan semua field fillable dapat di-update
     */
    public function test_temu_dokter_dapat_update_semua_fillable_fields()
    {
        $temuDokter = TemuDokter::factory()->create([
            'no_urut' => 5,
            'status' => TemuDokter::STATUS_MENUNGGU,
        ]);

        $newTime = Carbon::now()->addDays(5);
        $temuDokter->update([
            'no_urut' => 15,
            'status' => TemuDokter::STATUS_SELESAI,
            'waktu_daftar' => $newTime,
        ]);

        $this->assertDatabaseHas('temu_dokter', [
            'idreservasi_dokter' => $temuDokter->idreservasi_dokter,
            'no_urut' => 15,
            'status' => TemuDokter::STATUS_SELESAI,
        ]);
    }

    /**
     * TC-18: test_temu_dokter_delete_operation
     * Functionality: Delete operation
     * Object Being Tested: TemuDokter yang akan dihapus
     * Function: Memastikan appointment dapat dihapus dari database
     */
    public function test_temu_dokter_delete_operation()
    {
        $temuDokter = TemuDokter::factory()->create();
        $id = $temuDokter->idreservasi_dokter;

        $temuDokter->delete();

        $this->assertDatabaseMissing('temu_dokter', [
            'idreservasi_dokter' => $id,
        ]);
    }

    /**
     * TC-19: test_multiple_temu_dokter_dapat_dibuat
     * Functionality: Bulk operations
     * Object Being Tested: Multiple TemuDokter instances dengan variasi status
     * Function: Memastikan sistem dapat menangani multiple appointment secara bersamaan
     */
    public function test_multiple_temu_dokter_dapat_dibuat()
    {
        TemuDokter::factory()->count(10)->create();

        $this->assertCount(10, TemuDokter::all());
    }

    /**
     * TC-20: test_temu_dokter_retrieve_by_primary_key
     * Functionality: Retrieval operation
     * Object Being Tested: TemuDokter diakses melalui primary key
     * Function: Memastikan appointment dapat diambil dari database menggunakan primary key
     */
    public function test_temu_dokter_retrieve_by_primary_key()
    {
        $temuDokter = TemuDokter::factory()->create();

        $retrieved = TemuDokter::find($temuDokter->idreservasi_dokter);

        $this->assertNotNull($retrieved);
        $this->assertEquals($temuDokter->idreservasi_dokter, $retrieved->idreservasi_dokter);
        $this->assertEquals($temuDokter->no_urut, $retrieved->no_urut);
    }

    /**
     * TC-21: test_status_constants_nilai_benar
     * Functionality: Constant values verification
     * Object Being Tested: TemuDokter status constants
     * Function: Memastikan status constants memiliki nilai yang konsisten
     */
    public function test_status_constants_nilai_benar()
    {
        $this->assertEquals('0', TemuDokter::STATUS_MENUNGGU);
        $this->assertEquals('1', TemuDokter::STATUS_SELESAI);
        $this->assertEquals('2', TemuDokter::STATUS_BATAL);
    }

    /**
     * TC-22: test_temu_dokter_query_count
     * Functionality: Query operation
     * Object Being Tested: TemuDokter collection count
     * Function: Memastikan query count() berfungsi dengan benar
     */
    public function test_temu_dokter_query_count()
    {
        TemuDokter::factory()->count(5)->create();
        TemuDokter::factory()->count(3)->batal()->create();

        $totalCount = TemuDokter::count();
        $activeCount = TemuDokter::active()->count();

        $this->assertEquals(8, $totalCount);
        $this->assertEquals(5, $activeCount);
    }

    /**
     * TC-23: test_temu_dokter_order_by_waktu_daftar
     * Functionality: Query ordering
     * Object Being Tested: TemuDokter ordered by waktu_daftar
     * Function: Memastikan appointment dapat diurutkan berdasarkan waktu_daftar
     */
    public function test_temu_dokter_order_by_waktu_daftar()
    {
        $time1 = Carbon::now();
        $time2 = Carbon::now()->addDay();
        $time3 = Carbon::now()->addDays(2);

        TemuDokter::factory()->create(['waktu_daftar' => $time3]);
        TemuDokter::factory()->create(['waktu_daftar' => $time1]);
        TemuDokter::factory()->create(['waktu_daftar' => $time2]);

        $ordered = TemuDokter::orderBy('waktu_daftar')->get();

        $this->assertTrue($ordered[0]->waktu_daftar < $ordered[1]->waktu_daftar);
        $this->assertTrue($ordered[1]->waktu_daftar < $ordered[2]->waktu_daftar);
    }

    /**
     * TC-24: test_temu_dokter_filter_by_status
     * Functionality: Advanced filtering
     * Object Being Tested: TemuDokter filtered by specific status
     * Function: Memastikan appointment dapat difilter berdasarkan status tertentu
     */
    public function test_temu_dokter_filter_by_status()
    {
        TemuDokter::factory()->count(3)->menunggu()->create();
        TemuDokter::factory()->count(2)->selesai()->create();
        TemuDokter::factory()->count(1)->batal()->create();

        $menunggu = TemuDokter::where('status', TemuDokter::STATUS_MENUNGGU)->count();
        $selesai = TemuDokter::where('status', TemuDokter::STATUS_SELESAI)->count();
        $batal = TemuDokter::where('status', TemuDokter::STATUS_BATAL)->count();

        $this->assertEquals(3, $menunggu);
        $this->assertEquals(2, $selesai);
        $this->assertEquals(1, $batal);
    }
}
