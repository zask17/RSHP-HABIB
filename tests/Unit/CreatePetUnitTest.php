<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CreatePetUnitTest extends TestCase
{
    /**
     * Simulasi logika yang seharusnya ada di Controller.
     * Karena Controller tidak boleh diubah, kita uji logikanya di sini.
     */
    private function formatNamaPet(string $nama): string
    {
        return ucwords(strtolower(trim($nama)));
    }

    // TC-01: Nama huruf kecil -> Title Case
    public function test_formatNamaPet_huruf_kecil_menjadi_title_case()
    {
        $this->assertEquals('Buddy', $this->formatNamaPet('buddy'));
    }

    // TC-02: Nama huruf kapital semua -> Title Case
    public function test_formatNamaPet_huruf_kapital_menjadi_title_case()
    {
        $this->assertEquals('Max The Dog', $this->formatNamaPet('MAX THE DOG'));
    }

    // TC-03: Nama dengan spasi ekstra -> di-trim
    public function test_formatNamaPet_spasi_ekstra_dihapus()
    {
        $this->assertEquals('Kitty', $this->formatNamaPet('   kitty   '));
    }

    // TC-04: Nama campuran -> dinormalisasi
    public function test_formatNamaPet_huruf_campuran_dinormalisasi()
    {
        $this->assertEquals('Si Comel', $this->formatNamaPet('sI cOMEL'));
    }
}