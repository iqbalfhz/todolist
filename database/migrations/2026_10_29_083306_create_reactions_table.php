<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The subject is a task, a savings entry, or a memory, named by the
     * morph aliases in SubjectType.
     */
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('space_id')->constrained();
            $table->ulidMorphs('subject');
            $table->foreignUlid('user_id')->constrained();
            $table->string('emoji', 16);
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
        Schema::dropIfExists('reactions');
    }
};
