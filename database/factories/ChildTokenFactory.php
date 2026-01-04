<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\ChildToken;

class ChildTokenFactory extends Factory
{
    protected $model = ChildToken::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->generateUniqueToken(),
            'isused' => false,
            'useddate' => null,
        ];
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::upper(Str::random(rand(6, 8))); // e.g. A9F3KQ
        } while (ChildToken::where('uuid', $token)->exists());

        return $token;
    }
}

