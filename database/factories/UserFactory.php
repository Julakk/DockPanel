<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Hash dibagi ke semua user factory. bcrypt itu bersalt, jadi tanpa ini tiap
     * user punya hash beda, dan test yang ganti user lewat actingAs() dalam satu
     * sesi bakal ketendang AuthenticateSession (hash di sesi != hash user baru).
     */
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= bcrypt('password'),
            'remember_token' => Str::random(10),
            'root_admin' => false,
            'language' => 'id',
        ];
    }
}
