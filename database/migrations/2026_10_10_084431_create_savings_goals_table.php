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
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('space_id')->constrained();
            $table->string('name', 50);
            $table->string('emoji', 16)->nullable();
            $table->unsignedBigInteger('target_amount');
            $table->char('currency', 3);
            $table->date('target_date')->nullable();
            $table->dateTime('achieved_at', precision: 6)->nullable();
            $table->dateTime('achieved_notified_at', precision: 6)->nullable();
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
        Schema::dropIfExists('savings_goals');
    }
};
