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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_doctor_profile_id')->nullable();
            $table->unsignedBigInteger('sender_patient_id')->nullable();
            $table->unsignedBigInteger('sender_patient_member_id')->nullable();
            // Receiver can be either patient or patient_member
            $table->unsignedBigInteger('receiver_patient_id')->nullable();
            $table->unsignedBigInteger('receiver_patient_member_id')->nullable();
            $table->unsignedBigInteger('receiver_doctor_profile_id')->nullable();
            $table->string('file')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Foreign keys (add only if you want strict DB constraints)
            $table->foreign('sender_doctor_profile_id')->references('id')->on('doctor_profiles')->onDelete('cascade');
            $table->foreign('sender_patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('sender_patient_member_id')->references('id')->on('patient_members')->onDelete('cascade');

            $table->foreign('receiver_doctor_profile_id')->references('id')->on('doctor_profiles')->onDelete('cascade');
            $table->foreign('receiver_patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('receiver_patient_member_id')->references('id')->on('patient_members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
