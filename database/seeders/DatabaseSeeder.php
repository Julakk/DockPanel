<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@ahmadstore.id'],
            [
                'name' => 'Julak Junior',
                'password' => bcrypt('changeme123'), // GANTI setelah login pertama!
                'root_admin' => true,
            ]
        );
    }
}
