<?php

namespace Database\Factories;

use App\Models\RoleUser; // atau sesuai namespace model kamu
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TemuDokterFactory extends Factory
{
    protected $model = \App\Models\TemuDokter::class;

    public function definition(): array
    {
        return [
            'idrole_user'   => RoleUser::factory(), // atau gunakan existing idrole_user
            'waktu_daftar'  => Carbon::now()->addDays(rand(1, 10)),
            'no_urut'       => $this->faker->numberBetween(1, 50),
            'status'        => $this->faker->randomElement(['0', '1', '2']), // 0=Menunggu, 1=Selesai, 2=Batal
        ];
    }

    /**
     * State untuk status Menunggu
     */
    public function menunggu()
    {
        return $this->state(fn (array $attributes) => [
            'status' => '0',
        ]);
    }

    /**
     * State untuk status Selesai
     */
    public function selesai()
    {
        return $this->state(fn (array $attributes) => [
            'status' => '1',
        ]);
    }

    /**
     * State untuk status Batal
     */
    public function batal()
    {
        return $this->state(fn (array $attributes) => [
            'status' => '2',
        ]);
    }
}