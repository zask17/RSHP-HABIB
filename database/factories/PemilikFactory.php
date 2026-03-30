<?php

namespace Database\Factories;

use App\Models\Pemilik;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PemilikFactory extends Factory
{
    /**
     * Nama model yang terkait dengan factory ini.
     *
     * @var string
     */
    protected $model = Pemilik::class;

    /**
     * Definisi keadaan default model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Membuat idpemilik unik secara manual karena incrementing = false
            'idpemilik' => fake()->unique()->numberBetween(1, 9999),
            
            // Menghubungkan ke UserFactory
            'iduser' => User::factory(),
            
            // Menggunakan format nomor WhatsApp (max 45 sesuai migrasi)
            'no_wa' => fake()->numerify('08##########'),
            
            // Alamat (max 100 sesuai migrasi)
            'alamat' => fake()->address(),
        ];
    }
}