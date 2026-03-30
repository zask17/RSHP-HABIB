<?php

namespace Database\Factories;

use App\Models\RekamMedis;
use App\Models\Pet;
use App\Models\RoleUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class RekamMedisFactory extends Factory
{
    protected $model = RekamMedis::class;

    public function definition(): array
    {
        return [
            'idreservasi_dokter' => \App\Models\TemuDokter::factory(), // Asumsi ada factory TemuDokter
            'idpet' => Pet::factory(),
            'dokter_pemeriksa' => RoleUser::factory(), // idrole_user dari tabel role_user
            'anamnesa' => $this->faker->sentence(),
            'temuan_klinis' => $this->faker->paragraph(),
            'diagnosa' => $this->faker->word(),
            'created_at' => now(),
        ];
    }
}