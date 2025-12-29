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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();

            $table->string('path')->nullable()->index();

            $table->unsignedSmallInteger('level')->default(0);

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('include_in_menu')->default(false);
            $table->integer('position')->default(0);

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();

            $table->timestamps();

            $table->index(['parent_id', 'is_active', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
