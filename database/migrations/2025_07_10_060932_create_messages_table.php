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

            $table->unsignedBigInteger('consultation_id');
            $table->unsignedBigInteger('sender_id'); // users table
            $table->unsignedBigInteger('receiver_id'); // users table
            $table->unsignedBigInteger('patient_id'); // main patient id
            $table->unsignedBigInteger('patient_member_id')->nullable(); // যদি patient member message দেয়

            $table->text('content')->nullable();
            $table->string('file')->nullable();
            $table->boolean('is_read')->default(false);

            $table->timestamps();

            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('patient_member_id')->references('id')->on('patient_members')->onDelete('cascade');
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
