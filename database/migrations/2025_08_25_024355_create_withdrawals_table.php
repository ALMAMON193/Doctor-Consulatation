    <?php

    use App\Models\DoctorProfile;
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
            Schema::create('withdrawals', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(DoctorProfile::class)->constrained()->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('account_number')->nullable();
                $table->string('account_name')->nullable();
                $table->string('transaction_id')->nullable();
                $table->enum('status', ['pending', 'success', 'cancelled'])->default('pending');
                $table->text('remarks')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('withdrawals');
        }
    };
