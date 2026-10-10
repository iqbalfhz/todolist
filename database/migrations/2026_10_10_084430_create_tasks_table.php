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
        Schema::create('tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('task_list_id')->constrained();
            $table->foreignUlid('space_id')->constrained();
            $table->string('title', 200);
            $table->text('notes')->nullable();
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();
            $table->unsignedTinyInteger('priority')->default(0);
            $table->double('position');
            $table->dateTime('remind_at', precision: 6)->nullable();
            $table->dateTime('reminder_sent_at', precision: 6)->nullable();
            $table->dateTime('completed_at', precision: 6)->nullable();
            $table->foreignUlid('completed_by')->nullable()->constrained('users');
            $table->foreignUlid('created_by')->constrained('users');
            $table->foreignUlid('updated_by')->constrained('users');
            $table->timestamps(6);
            $table->softDeletes(precision: 6);

            $table->index(['space_id', 'updated_at', 'id']);
            $table->index(['reminder_sent_at', 'remind_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
