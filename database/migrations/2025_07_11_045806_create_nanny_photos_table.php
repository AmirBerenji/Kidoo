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
        Schema::create('nanny_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nanny_id')->constrained()->onDelete('cascade');
            $table->string('photo_url');
            $table->boolean('is_profile')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nanny_photos');
    }
};
