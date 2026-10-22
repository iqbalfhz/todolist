<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The table now also records the trip reminders, which go out at the same
     * time of day. Keys: "date:{special_date_id}", "birthday:{space_id}:{user_id}",
     * and "trip:{trip_id}:{start_date}".
     */
    public function up(): void
    {
        Schema::rename('special_date_reminders', 'daily_reminders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('daily_reminders', 'special_date_reminders');
    }
};
