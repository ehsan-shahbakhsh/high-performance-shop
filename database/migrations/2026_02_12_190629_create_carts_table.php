<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\{CartStatus, CartType};

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('type', CartType::cases())->default(CartType::Main)->index();

            $table->enum('status', CartStatus::cases())->default(CartStatus::Active)->index();

            $table->json('meta')->nullable();

            $table->timestamp('locked_at')->nullable();
            $table->uuid('lock_token')->nullable();

            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();

//            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('items_qty_sum')->default(0);

            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('shipping_total')->default(0);
            $table->unsignedBigInteger('total')->default(0);

            $table->unsignedInteger('version')->default(0);

            $table->timestamps();

            $table->index(['user_id', 'status']);

            $table->index(['user_id', 'type', 'status']);

            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
