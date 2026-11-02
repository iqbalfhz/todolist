<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One emailed code per account and purpose: resetting the password or
     * verifying the email. Only an HMAC of the code is stored, and a code
     * goes once it is used, expires, or is guessed wrong too often.
     */
    public function up(): void
    {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 32);
            $table->char('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('expires_at', precision: 6);
            $table->timestamps(6);

            $table->unique(['user_id', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
