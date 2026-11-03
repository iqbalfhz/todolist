<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The latest daily reference rates of the ECB, one row per base currency
     * (only the euro), for the savings estimates in the app. The rates are
     * kept as the decimal text the ECB publishes, so no rounding creeps in.
     */
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('base', 3)->unique();
            $table->date('rate_date');
            $table->json('rates');
            $table->dateTime('fetched_at', precision: 6);
            $table->timestamps(6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
