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
        Schema::create('task_lists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('space_id')->constrained();
            $table->string('name', 50);
            $table->string('emoji', 16)->nullable();
            $table->string('color', 16)->default('mawar');
            $table->double('position');
            $table->dateTime('archived_at', precision: 6)->nullable();
            $table->foreignUlid('created_by')->constrained('users');
            $table->timestamps(6);
            $table->softDeletes(precision: 6);

            $table->index(['space_id', 'updated_at', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_lists');
    }
};
