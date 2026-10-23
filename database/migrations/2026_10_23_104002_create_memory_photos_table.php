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
        Schema::create('memory_photos', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('memory_id')->constrained();
            $table->foreignUlid('space_id')->constrained();
            $table->foreignUlid('uploaded_by')->constrained('users');
            $table->string('path');
            $table->string('thumb_path');
            $table->unsignedInteger('size_bytes');
            $table->unsignedSmallInteger('width');
            $table->unsignedSmallInteger('height');
            $table->dateTime('taken_at', precision: 6)->nullable();
            $table->double('position');
            $table->timestamps(6);
            $table->softDeletes(precision: 6);

            $table->index(['space_id', 'updated_at', 'id']);
            $table->index(['memory_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memory_photos');
    }
};
