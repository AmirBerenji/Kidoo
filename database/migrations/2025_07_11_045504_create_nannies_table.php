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
        Schema::create('nannies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->enum('gender', ['Female', 'Male', 'Other']);
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('years_experience');
            $table->string('working_hours')->nullable();
            $table->text('days_available')->nullable();
            $table->enum('commitment_type', ['Short-term', 'Long-term','Temporary'])->nullable();
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->text('fixed_package_description')->nullable();
            $table->boolean('contact_enabled')->default(true);
            $table->enum('booking_type', ['Direct', 'Interview','On Request'])->nullable();
            $table->json('availability_calendar')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('video_intro_url')->nullable();
            $table->string('resume_url')->nullable();
            $table->string('age_groups')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nannies');
    }
};
