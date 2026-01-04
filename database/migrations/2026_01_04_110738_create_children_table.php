<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('image')->nullable();
            $table->string('name');
            $table->string('last_name');
            $table->string('address')->nullable();
            $table->date('birthday')->nullable();
            $table->string('blood_type')->nullable();
            $table->string('gender')->nullable();
            $table->string('uuid')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
