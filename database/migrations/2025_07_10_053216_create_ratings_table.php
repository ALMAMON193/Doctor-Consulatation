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

            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('doctor_profile_id');
            $table->unsignedBigInteger('patient_member_id')->nullable();

            $table->tinyInteger('rating')->comment('1 to 5 stars');
            $table->text('review')->nullable();

            $table->timestamps();

            // Foreign Keys
            $table->foreign('patient_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('doctor_profile_id')->references('id')->on('doctor_profiles')->onDelete('cascade');
            $table->foreign('patient_member_id')->references('id')->on('patient_members')->onDelete('set null');
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
