<?php

namespace Database\Factories;

use App\Models\RasHewan;
use App\Models\JenisHewan;
use Illuminate\Database\Eloquent\Factories\Factory;

class RasHewanFactory extends Factory
{
    protected $model = RasHewan::class;

    public function definition(): array
    {
        return [
            'nama_ras' => fake()->word(),
            'idjenis_hewan' => JenisHewan::factory(),
        ];
    }
}