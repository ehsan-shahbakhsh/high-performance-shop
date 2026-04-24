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
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('discount_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('operator', 20);

            $table->string('value_string')->nullable();
            $table->bigInteger('value_integer')->nullable();
            $table->decimal('value_float', 16, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_rules');
    }
};
