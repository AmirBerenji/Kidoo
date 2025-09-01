<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nanny_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nanny_id')->constrained()->onDelete('cascade');
            $table->string('language_code', 5); // 'en', 'hy', 'ru'
            $table->string('full_name');
            $table->string('specialization')->nullable();
            $table->string('services_provided')->nullable();
            $table->string('special_cases')->nullable();
            $table->string('fixed_package_title')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nanny_translations');
    }
};
