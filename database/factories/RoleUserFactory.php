<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleUserFactory extends Factory
{
    protected $model = RoleUser::class;

    public function definition(): array
    {
        return [
            'iduser' => User::factory(),
            'idrole' => Role::factory(),   // atau Role::firstOrCreate jika ingin lebih aman
            'status' => 1,
        ];
    }

    // Optional: state untuk role tertentu
    public function asDokter()
    {
        return $this->state(function (array $attributes) {
            return [
                'idrole' => Role::firstOrCreate(['nama_role' => 'Dokter'])->idrole,
            ];
        });
    }
}