<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per reminder that was sent, so each one goes out at most once.
     * The key names what the reminder is about: "date:{special_date_id}" or
     * "birthday:{space_id}:{user_id}".
     */
    public function up(): void
    {
        Schema::create('special_date_reminders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reminder_key', 80);
            $table->date('remind_on');
            $table->timestamps(6);

            $table->unique(['reminder_key', 'remind_on']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_date_reminders');
    }
};
