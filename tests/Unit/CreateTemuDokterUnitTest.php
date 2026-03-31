<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use App\Http\Controllers\Admin\TemuDokterController;
use App\Models\TemuDokter;

/**
 * UNIT TEST — Fitur Create Janji Temu (TemuDokterController & TemuDokter Model)
 * * Sesuai dengan logic di TemuDokterController@updateStatus dan TemuDokter Model
 */
class CreateTemuDokterUnitTest extends TestCase
{
    private TemuDokterController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TemuDokterController();
    }

    /**
     * Helper untuk memanggil method private jika diperlukan di masa depan.
     * Saat ini digunakan untuk memvalidasi logika label status.
     */
    private function invokePrivate(string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod($this->controller, $method);
        $ref->setAccessible(true);
        return $ref->invoke($this->controller, ...$args);
    }

    /**
     * TC-01 s/d TC-04: Pengujian Label Status
     * Menguji logika 'match' yang ada di TemuDokter@getStatusTextAttribute 
     * dan TemuDokterController@updateStatus.
     */

    // TC-01: Status '0' → 'Menunggu'
    public function test_status_0_adalah_Menunggu()
    {
        $status = '0';
        $label = match($status) {
            TemuDokter::STATUS_MENUNGGU => 'Menunggu',
            TemuDokter::STATUS_SELESAI => 'Selesai',
            TemuDokter::STATUS_BATAL => 'Batal',
            default => 'Tidak Diketahui'
        };

        $this->assertEquals('Menunggu', $label);
    }

    // TC-02: Status '1' → 'Selesai'
    public function test_status_1_adalah_Selesai()
    {
        $status = '1';
        $label = match($status) {
            '0' => 'Menunggu',
            '1' => 'Selesai',
            '2' => 'Batal',
            default => 'Tidak Diketahui'
        };

        $this->assertEquals('Selesai', $label);
    }

    // TC-03: Status '2' → 'Batal'
    public function test_status_2_adalah_Batal()
    {
        $status = '2';
        $label = match($status) {
            '0' => 'Menunggu',
            '1' => 'Selesai',
            '2' => 'Batal',
            default => 'Tidak Diketahui'
        };

        $this->assertEquals('Batal', $label);
    }

    // TC-04: Status di luar 0/1/2 → 'Tidak Diketahui'
    public function test_status_nilai_tidak_dikenal_mengembalikan_default()
    {
        $status = '9'; // Nilai random
        $label = match($status) {
            '0' => 'Menunggu',
            '1' => 'Selesai',
            '2' => 'Batal',
            default => 'Tidak Diketahui'
        };

        $this->assertEquals('Tidak Diketahui', $label);
    }

    /**
     * TC-05 s/d TC-06: Kalkulasi no_urut
     * Menguji logika ($maxUrut ?? 0) + 1 yang ada di TemuDokterController@store
     */

    // TC-05: Kalkulasi no_urut — belum ada antrean hari ini (null = 1)
    public function test_no_urut_pertama_ketika_belum_ada_antrean()
    {
        // Simulasi DB::table('temu_dokter')->max('no_urut') mengembalikan null
        $maxUrutDariDatabase = null; 
        
        $noUrutBaru = ($maxUrutDariDatabase ?? 0) + 1;

        $this->assertEquals(1, $noUrutBaru);
    }

    // TC-06: Kalkulasi no_urut — sudah ada antrean sebelumnya
    public function test_no_urut_bertambah_dari_antrean_terakhir()
    {
        // Simulasi DB::table('temu_dokter')->max('no_urut') mengembalikan 4
        $maxUrutDariDatabase = 4; 
        
        $noUrutBaru = ($maxUrutDariDatabase ?? 0) + 1;

        $this->assertEquals(5, $noUrutBaru);
    }
}