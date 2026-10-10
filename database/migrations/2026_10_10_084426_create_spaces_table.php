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
        Schema::create('spaces', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('type', 16);
            $table->string('timezone', 64);
            $table->foreignUlid('owner_id')->constrained('users');
            $table->timestamps(6);
            $table->softDeletes(precision: 6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spaces');
    }
};
