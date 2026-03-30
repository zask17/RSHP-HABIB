<?php


namespace Database\Factories;


use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;


class UserFactory extends Factory
{
   protected $model = User::class;


   public function definition(): array
   {
       return [
           'nama' => fake()->name(),
           'email' => fake()->unique()->safeEmail(),
           'email_verified_at' => now(),
           'password' => Hash::make('password'),
           'deleted_at' => null,
           'deleted_by' => null,
       ];
   }
   /**
    * State untuk user yang sudah dihapus (soft delete).
    */
   public function deleted(): static
   {
       return $this->state(fn (array $attributes) => [
           'deleted_at' => now(),
           'deleted_by' => 1,
       ]);
   }
}

