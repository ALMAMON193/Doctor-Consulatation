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
            $table->json('specialization')->nullable();
            $table->string('cpf_bank')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_type')->nullable();
            $table->string('account_number')->nullable();
            $table->string('dv')->nullable();
            $table->string('current_account_number')->nullable();
            $table->string('current_dv')->nullable();
            $table->string('crm')->unique()->nullable();
            $table->string('uf')->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->time('consultation_time')->nullable()->default('00:10:00');
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->decimal('company_income', 10, 2)->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('zipcode')->nullable();
            $table->string ('address')->nullable ();
            $table->string('house_number')->nullable();
            $table->string('road_number')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('complement')->nullable();
            $table->string('personal_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('cpf_personal')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('video_path')->nullable();
            $table->string('profile_picture')->nullable();
            $table->text('bio')->nullable();
            $table->enum('subscription_status', ['basic', 'pro','no_subscription'])->default('no_subscription');
            $table->enum('verification_status', ['pending', 'verified', 'rejected','unverified'])->default('pending');
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
