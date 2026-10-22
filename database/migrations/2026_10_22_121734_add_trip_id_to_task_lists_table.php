<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A list with a trip_id is the packing checklist of that trip. A trip has
     * at most one, and the checklist only goes away together with its trip.
     */
    public function up(): void
    {
        Schema::table('task_lists', function (Blueprint $table) {
            $table->foreignUlid('trip_id')->nullable()->unique()->after('space_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_lists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trip_id');
        });
    }
};
