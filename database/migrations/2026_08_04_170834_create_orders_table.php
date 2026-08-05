<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Sales\OrderStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained();

            $table->string('status');
            $table->string('payment_status');

            $table->unsignedBigInteger('items_subtotal');
            $table->unsignedBigInteger('items_sale_discount')->default(0);
            $table->unsignedBigInteger('cart_discount')->default(0);

            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('shipping_total')->default(0);
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('grand_total');

            $table->json('discount_breakdown')->nullable();

            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->json('shipping_address_snapshot')->nullable();

            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->json('shipping_method_snapshot')->nullable();

            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('payment_driver')->nullable();
            $table->json('payment_method_snapshot')->nullable();

            $table->string('tracking_number')->nullable();
            $table->text('customer_notes')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'payment_status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
