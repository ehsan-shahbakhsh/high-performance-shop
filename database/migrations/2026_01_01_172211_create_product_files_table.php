<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('display_name');
            $table->string('filename');
            $table->string('disk')->default('local');
            $table->string('storage_path');

            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime_type')->nullable();

            $table->unsignedInteger('download_limit')->nullable();
            $table->unsignedInteger('expiry_days')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_files');
    }
};
