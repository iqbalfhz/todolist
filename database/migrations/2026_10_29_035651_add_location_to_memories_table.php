<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Six decimals keep a pin within about 11 cm of where it was placed.
     */
    public function up(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->decimal('latitude', 8, 6)->nullable()->after('place_name');
            $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
