<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Sales\{TransactionType, TransactionStatus};

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('payment_method_id')->constrained();

            $table->enum('type', TransactionType::cases())->default(TransactionType::Payment);

            $table->unsignedBigInteger('amount');

            $table->enum('status', TransactionStatus::cases())->default(TransactionStatus::Pending);

            $table->string('token')->nullable();
            $table->string('reference_id')->nullable();
            $table->json('gateway_payload')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->string('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unsignedTinyInteger('unique_keeper')
                ->virtualAs('IF(deleted_at IS NULL, 1, NULL)');

            $table->unique(['token', 'unique_keeper']);

            $table->unique(['reference_id', 'unique_keeper']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
