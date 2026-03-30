<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'nama_role' => $this->faker->unique()->word(),
        ];
    }

    public function administrator()
    {
        return $this->state(['nama_role' => 'Administrator']);
    }

    public function resepsionis()
    {
        return $this->state(['nama_role' => 'Resepsionis']);
    }

    public function dokter()
    {
        return $this->state(['nama_role' => 'Dokter']);
    }
}