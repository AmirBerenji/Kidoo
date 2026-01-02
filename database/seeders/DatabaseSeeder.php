<?php

namespace Database\Seeders;

use App\Models\ChildToken;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        ChildToken::factory()->count(1000)->create();


        $this->call([
            LanguageSeeder::class,
        ]);


    }
}
