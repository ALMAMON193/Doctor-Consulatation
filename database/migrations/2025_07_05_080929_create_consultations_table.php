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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('doctor_profile_id');
            $table->unsignedBigInteger('patient_member_id')->nullable();
            $table->decimal('fee_amount', 8, 2);
            $table->string('coupon_code')->nullable();
            $table->decimal('discount_amount', 8, 2)->default(0.00);
            $table->decimal('final_amount', 8, 2);
            $table->text('complaint')->nullable();
            $table->integer('pain_level')->nullable()->default(0);
            $table->date('consultation_date')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'completed', 'cancelled'])->default('pending');
            $table->enum('consultation_status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('doctor_profile_id')->references('id')->on('doctor_profiles')->onDelete('cascade');
            $table->foreign('patient_member_id')->references('id')->on('patient_members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
