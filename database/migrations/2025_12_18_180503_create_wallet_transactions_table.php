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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('type', 30)->index();
            $table->bigInteger('amount');

            $table->unsignedBigInteger('balance_before');
            $table->unsignedBigInteger('balance_after');

            $table->boolean('confirmed')->default(true);
            $table->nullableMorphs('related');

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['wallet_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
