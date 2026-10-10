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
        Schema::table('users', function (Blueprint $table) {
            $table->string('color', 16)->default('mawar')->after('email');
            $table->string('timezone', 64)->default('Asia/Jakarta')->after('color');
            $table->string('locale', 8)->default('id')->after('timezone');
            $table->char('default_currency', 3)->default('IDR')->after('locale');
            $table->string('birth_date', 10)->nullable()->after('default_currency');
            $table->json('notification_prefs')->nullable()->after('birth_date');
            $table->string('avatar_path')->nullable()->after('notification_prefs');
            $table->unsignedInteger('avatar_version')->default(0)->after('avatar_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'color',
                'timezone',
                'locale',
                'default_currency',
                'birth_date',
                'notification_prefs',
                'avatar_path',
                'avatar_version',
            ]);
        });
    }
};
