<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A task with a frequency repeats every repeat_interval days, weeks,
     * months, or years, counted from repeat_anchor, the due date the user
     * last chose. Completing it creates the next occurrence once, which
     * next_task_id points to.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('repeat_frequency', 16)->nullable()->after('due_time');
            $table->unsignedTinyInteger('repeat_interval')->nullable()->after('repeat_frequency');
            $table->date('repeat_anchor')->nullable()->after('repeat_interval');
            $table->foreignUlid('next_task_id')->nullable()->unique()->after('completed_by')->constrained('tasks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('next_task_id');
            $table->dropColumn(['repeat_frequency', 'repeat_interval', 'repeat_anchor']);
        });
    }
};
