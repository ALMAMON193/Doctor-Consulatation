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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\DoctorProfile::class)->nullable(); // Doctor being rated
            $table->foreignIdFor(\App\Models\Patient::class)->nullable(); // Patient being rated
            $table->foreignIdFor(\App\Models\PatientMember::class)->nullable(); // Family member
            $table->unsignedBigInteger('given_by_id'); // User who gave rating
            $table->enum('given_by_type',['patient','doctor']);
            $table->tinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
