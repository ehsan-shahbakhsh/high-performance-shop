<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\AttributeType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attribute_group_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('code')->unique();
            $table->string('name');

            $table->enum('type', AttributeType::cases());

            $table->boolean('is_filterable')->default(false)->index();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_variant')->default(false)->index();

            $table->unsignedInteger('position')->default(0)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
