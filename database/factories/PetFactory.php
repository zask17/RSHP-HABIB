<?php

namespace Database\Factories;

use App\Models\Pet;
use App\Models\Pemilik;
use App\Models\RasHewan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PetFactory extends Factory
{
    protected $model = Pet::class;

    public function definition(): array
    {
        return [
            'nama' => fake()->firstName(),
            'tanggal_lahir' => fake()->date('Y-m-d', 'now'),
            'warna_tanda' => fake()->safeColorName(),
            'jenis_kelamin' => fake()->randomElement(['M', 'F']),
            // Menghubungkan ke PemilikFactory
            'idpemilik' => Pemilik::factory(), 
            // Memperbaiki error: Memicu factory RasHewan agar ID selalu valid di database
            'idras_hewan' => RasHewan::factory(), 
            'deleted_at' => null,
            'deleted_by' => null,
        ];
    }
}