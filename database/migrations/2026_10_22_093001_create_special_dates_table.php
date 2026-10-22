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
        Schema::create('special_dates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('space_id')->constrained();
            $table->string('type', 16);
            $table->string('title', 50);
            // YYYY-MM-DD, or --MM-DD when the year is not known, like users.birth_date.
            $table->string('date', 10);
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
        Schema::dropIfExists('special_dates');
    }
};
