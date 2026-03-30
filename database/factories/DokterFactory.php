<?php

namespace Database\Factories;

use App\Models\Dokter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DokterFactory extends Factory
{
    /**
     * Nama model yang terkait dengan factory ini.
     *
     * @var string
     */
    protected $model = Dokter::class;

    /**
     * Definisi keadaan default model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Menghubungkan ke UserFactory untuk membuat user baru secara otomatis
            'iduser' => User::factory(), 
            
            // Menggunakan data palsu sesuai dengan batasan panjang di migrasi (max 100)
            'alamat' => fake()->address(),
            
            // Memastikan data no_hp berupa angka untuk memenuhi validasi numerik
            'no_hp' => fake()->numerify('08##########'), 
            
            'bidang_dokter' => fake()->randomElement([
                'Spesialis Anak', 
                'Spesialis Bedah', 
                'Dokter Umum', 
                'Spesialis Penyakit Dalam', 
                'Spesialis Jantung'
            ]),
            
            // Sesuai dengan enum ['M', 'F'] di migrasi
            'jenis_kelamin' => fake()->randomElement(['M', 'F']),
        ];
    }

    /**
     * State khusus untuk dokter laki-laki.
     */
    public function male(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_kelamin' => 'M',
        ]);
    }

    /**
     * State khusus untuk dokter perempuan.
     */
    public function female(): static
    {
        return $this->state(fn (array $attributes) => [
            'jenis_kelamin' => 'F',
        ]);
    }
}