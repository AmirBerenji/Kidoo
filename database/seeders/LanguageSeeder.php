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
    }
}
