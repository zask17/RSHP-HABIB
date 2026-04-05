<?php

namespace Tests\Unit;

use Tests\TestCase;
use ReflectionMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\PetController;
use App\Models\{Pet, Pemilik, RasHewan, JenisHewan, User, Role};
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * SKENARIO PENGUJIAN:
 * [Nama Test] [Functionality/Usability] [Object: Pet] [Function: Create via Reflection]
 */
class CreatePetUnitTest extends TestCase
{
    use RefreshDatabase;

    private PetController $controller;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Role Administrator (Infrastruktur pendukung unit)
        $roleAdmin = Role::create(['idrole' => 1, 'nama_role' => 'Administrator']);

        // 2. Setup User & Login (Isolasi lingkungan pengujian)
        $this->user = User::create([
            'iduser' => 1,
            'nama' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password')
        ]);
        $this->user->roles()->attach($roleAdmin->idrole, ['status' => 1]);
        Auth::login($this->user);

        // 3. Setup Master Data (Data structures integrity)
        $jenis = JenisHewan::create(['idjenis_hewan' => 1, 'nama_jenis_hewan' => 'Kucing']);
        RasHewan::create(['idras_hewan' => 1, 'nama_ras' => 'Persia', 'idjenis_hewan' => $jenis->idjenis_hewan]);
        Pemilik::create([
            'idpemilik' => 1234, 
            'iduser' => $this->user->iduser, 
            'no_wa' => '081234567890', 
            'alamat' => 'Surabaya'
        ]);

        // 4. Init Controller sebagai unit yang diuji
        $this->controller = new PetController();
    }

    /**
     * Helper White Box: Mengakses method internal menggunakan ReflectionMethod.
     * Memungkinkan pengujian memeriksa independen path dan struktur kontrol
     */
    private function invokeStore(Request $request)
    {
        $method = new ReflectionMethod($this->controller, 'store');
        // Memastikan method dapat diakses meskipun bersifat protected/private (White Box access)
        $method->setAccessible(true); 
        return $method->invoke($this->controller, $request);
    }



    // ========== POSITIVE TEST CASES (Functionality) ==========

    /**
     * UT-PET-001: Functionality - Pet - Create Model Persistence
     * Memeriksa flow informasi ke program dengan benar (Interface Testing).
     */
    public function test_create_functionality_pet_modelPersistence()
    {
        $request = new Request([
            'nama' => 'Muezza',
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => 1234,
            'tanggal_lahir' => '2024-01-01'
        ]);

        $response = $this->invokeStore($request);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertDatabaseHas('pet', ['nama' => 'Muezza', 'idpemilik' => 1234]);
    }

    /**
     * UT-PET-02: Functionality - Pet - Nullable Tanggal Lahir
     * Memeriksa penanganan data opsional dalam struktur kontrol
     */
    public function test_create_functionality_pet_nullableTanggalLahir()
    {
        $request = new Request([
            'nama' => 'Muezza',
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => 1234,
            'tanggal_lahir' => null
        ]);

        $this->invokeStore($request);
        $this->assertDatabaseHas('pet', ['nama' => 'Muezza', 'tanggal_lahir' => null]);
    }

    /**
     * UT-PET-003: Functionality - Pet - Nullable Warna Tanda
     * * Memeriksa penanganan data opsional dalam struktur kontrol
     */
    public function test_create_functionality_pet_nullableWarnaTanda()
    {
        $request = new Request([
            'nama' => 'Muezza',
            'jenis_kelamin' => 'F',
            'idras_hewan' => 1,
            'idpemilik' => 1234,
            'warna_tanda' => null
        ]);

        $this->invokeStore($request);
        $this->assertDatabaseHas('pet', ['nama' => 'Muezza', 'warna_tanda' => null]);
    }

    
    // ========== NEGATIVE TEST CASES (Robustness) ==========

    /**
     * UT-PET-N01: Usability - Pet - Validation: Required Field (Nama)
     * Menguji error-handling path saat input tidak sesuai batasan.
     */
    public function test_create_usability_pet_validationNamaRequired()
    {
        $request = new Request(['nama' => null, 'jenis_kelamin' => 'F', 'idras_hewan' => 1, 'idpemilik' => 1234]);

        try {
            $this->invokeStore($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('nama', $e->errors());
        }

        $this->assertDatabaseMissing('pet', ['idpemilik' => 1234, 'nama' => null]);
    }

    /**
     * UT-PET-N02: Usability - Pet - Validation: Required Field (Jenis Kelamin)
     */
    public function test_create_usability_pet_validationJenisKelaminRequired()
    {
        $request = new Request(['nama' => 'Muezza', 'jenis_kelamin' => null, 'idras_hewan' => 1, 'idpemilik' => 1234]);

        try {
            $this->invokeStore($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('jenis_kelamin', $e->errors());
        }

        $this->assertDatabaseMissing('pet', ['nama' => 'Muezza', 'jenis_kelamin' => null]);
    }

    /**
     * UT-PET-N03: Usability - Pet - Validation: Foreign Key (Ras Hewan)
     * Menguji boundary conditions (batasan data luar)
     */
    public function test_create_usability_pet_validationRasHewanRequired()
    {
        $request = new Request(['nama' => 'Muezza', 'jenis_kelamin' => 'F', 'idras_hewan' => null, 'idpemilik' => 1234]);

        try {
            $this->invokeStore($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('idras_hewan', $e->errors());
        }

        $this->assertDatabaseMissing('pet', ['nama' => 'Muezza', 'idras_hewan' => null]);
    }

    /**
     * UT-PET-N04: Usability - Pet - Validation: Foreign Key (Pemilik)
     */
    public function test_create_usability_pet_validationPemilikRequired()
    {
        $request = new Request(['nama' => 'Muezza', 'jenis_kelamin' => 'F', 'idras_hewan' => 1, 'idpemilik' => null]);

        try {
            $this->invokeStore($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('idpemilik', $e->errors());
        }

        $this->assertDatabaseMissing('pet', ['nama' => 'Muezza', 'idpemilik' => null]);
    }

    /**
     * UT-PET-N05: Usability - Pet - Validation: Invalid Foreign Key (Ras Hewan)
     */
    public function test_create_usability_pet_validationRasHewanInvalid()
    {
        $request = new Request(['nama' => 'Muezza', 'jenis_kelamin' => 'F', 'idras_hewan' => 9999, 'idpemilik' => 1234]);

        try {
            $this->invokeStore($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('idras_hewan', $e->errors());
        }

        $this->assertDatabaseMissing('pet', ['idras_hewan' => 9999]);
    }

    /**
     * UT-PET-N06: Usability - Pet - Validation: Invalid Foreign Key (Pemilik)
     */
    public function test_create_usability_pet_validationPemilikInvalid()
    {
        $request = new Request(['nama' => 'Muezza', 'jenis_kelamin' => 'F', 'idras_hewan' => 1, 'idpemilik' => 9999]);

        try {
            $this->invokeStore($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('idpemilik', $e->errors());
        }

        $this->assertDatabaseMissing('pet', ['idpemilik' => 9999]);
    }

    /**
     * UT-PET-CR-N07: Usability - Pet - Validation: Invalid Jenis Kelamin (X)
     * Memastikan error-handling path dieksekusi saat input ilegal dimasukkan
     */
    public function test_create_usability_pet_validationJenisKelaminInvalid()
    {
        $request = new Request(['nama' => 'Muezza', 'jenis_kelamin' => 'X', 'idras_hewan' => 1, 'idpemilik' => 1234]);

        try {
            $this->invokeStore($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('jenis_kelamin', $e->errors());
        }

        $this->assertDatabaseMissing('pet', ['nama' => 'Muezza', 'jenis_kelamin' => 'X']);
    }
}