<?php

namespace Database\Factories;

use App\Models\Perawat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerawatFactory extends Factory
{
    protected $model = Perawat::class;

    public function definition(): array
    {
        return [
            'iduser' => User::factory(), 
            'alamat' => fake()->address(),
            'no_hp' => fake()->numerify('08##########'), 
            'pendidikan' => fake()->randomElement(['D3 Keperawatan', 'S1 Keperawatan', 'Ners']),
            'jenis_kelamin' => fake()->randomElement(['M', 'F']),
        ];
    }
}