<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('languages')->insert([
            ['code' => 'en', 'name' => 'English'],
            ['code' => 'hy', 'name' => 'Armenian'],
            ['code' => 'ru', 'name' => 'Russian'],
        ]);
        DB::table('locations')->insert([
            ['city' => 'Yerevan', 'postal_code' => '0055'],
            ['city' => 'Edjmiacin', 'postal_code' => '0056'],
            ['city' => 'Gyumri', 'postal_code' => '0057'],
            ['city' => 'Hrazdan', 'postal_code' => '0058'],
            ['city' => 'Dilijan', 'postal_code' => '0058'],
            ['city' => 'Jermuk', 'postal_code' => '0058'],
        ]);
        DB::table('roles')->insert([
            ['name' => 'parent'],
            ['name' => 'doctor'],
            ['name' => 'nurse'],
            ['name' => 'admin'],
        ]);
        DB::table('child_tokens')->insert([
            ['uuid' => 'AMIRBE', 'isused' => '0'],
            ['uuid' => 'TESTTT', 'isused' => '0'],
            ['uuid' => 'MARALAM', 'isused' => '0'],
        ]);

    }
}
