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
        Schema::create('space_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('code', 6)->unique();
            $table->foreignUlid('created_by')->constrained('users');
            $table->foreignUlid('space_id')->nullable()->constrained();
            $table->foreignUlid('accepted_by')->nullable()->constrained('users');
            $table->dateTime('expires_at', precision: 6);
            $table->dateTime('accepted_at', precision: 6)->nullable();
            $table->dateTime('revoked_at', precision: 6)->nullable();
            $table->timestamps(6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_invitations');
    }
};
