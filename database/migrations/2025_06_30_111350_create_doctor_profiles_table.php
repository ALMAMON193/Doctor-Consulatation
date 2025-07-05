<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctor_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('additional_medical_record_number')->nullable();
            $table->string('specialization')->nullable();
            $table->string('cpf_bank')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_number')->nullable();
            $table->string('dv')->nullable();
            $table->string('crm')->unique()->nullable();
            $table->string('uf')->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->decimal('company_income', 10, 2)->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('address_zipcode')->nullable();
            $table->string('address_number')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_neighborhood')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_complement')->nullable();
            $table->string('personal_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('cpf_personal')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('video_path')->nullable();
            $table->string('profile_picture')->nullable();
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('verification_rejection_reason')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_profiles');
    }
};
