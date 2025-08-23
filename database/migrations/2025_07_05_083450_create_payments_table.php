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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consultation_id');
            $table->string('payment_intent_id')->nullable(); // Stripe payment intent ID
            $table->decimal('amount', 8, 2);
            $table->string('currency')->default('INR'); // Assuming Rs from the image
            $table->enum('payment_method', ['pix','card'])->default('card');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
            $table->foreign('consultation_id')->references('id')->on('consultations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
