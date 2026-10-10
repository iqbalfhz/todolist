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
        Schema::create('savings_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('savings_goal_id')->constrained();
            $table->foreignUlid('space_id')->constrained();
            $table->foreignUlid('user_id')->constrained();
            $table->string('type', 16)->default('deposit');
            $table->unsignedBigInteger('amount');
            $table->date('entry_date');
            $table->string('note', 200)->nullable();
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
        Schema::dropIfExists('savings_entries');
    }
};
