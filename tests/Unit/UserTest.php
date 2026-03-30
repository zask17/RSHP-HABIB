<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
// use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }

    // public function test_create_user()
    // {
    //     $userData = [
    //         'name' => 'John Doe',
    //         'email' => 'johndoe@example.com',
    //         'password' => bcrypt('password123'),
    //     ];

    //     $user = User::create($userData);

    //     $this->assertInstanceOf(User::class, $user);
    //     $this->assertEquals('John Doe', $user->name);
    //     $this->assertEquals('johndoe@example.com', $user->email);
    // }

    public function test_create_user()
    {
        $userData = [
            'name' => '',
            'email' => '',
            'password' => bcrypt(''),
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('', $user->name);
        $this->assertEquals('', $user->email);
    }
}
