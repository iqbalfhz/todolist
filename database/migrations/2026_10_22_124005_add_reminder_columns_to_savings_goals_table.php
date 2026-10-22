<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A goal with a frequency is reminded every week on the ISO weekday in
     * reminder_day (1 = Monday), or every month on that day of the month,
     * falling back to the last day of a shorter month.
     */
    public function up(): void
    {
        Schema::table('savings_goals', function (Blueprint $table) {
            $table->string('reminder_frequency', 16)->nullable()->after('target_date');
            $table->unsignedTinyInteger('reminder_day')->nullable()->after('reminder_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('savings_goals', function (Blueprint $table) {
            $table->dropColumn(['reminder_frequency', 'reminder_day']);
        });
    }
};
