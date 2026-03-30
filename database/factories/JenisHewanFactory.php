<?php

namespace Database\Factories;

use App\Models\JenisHewan;
use Illuminate\Database\Eloquent\Factories\Factory;

class JenisHewanFactory extends Factory
{
    protected $model = JenisHewan::class;

    public function definition(): array
    {
        return [
            'nama_jenis_hewan' => fake()->randomElement(['Kucing', 'Anjing', 'Kelinci']),
        ];
    }
}