<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->integer('year')->nullable()->after('badge_id');
            $table->integer('month')->nullable()->after('year');
        });

        // ユニーク制約を更新（user_id, badge_id, year, month）
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'badge_id']);
            $table->unique(['user_id', 'badge_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'badge_id', 'year', 'month']);
            $table->unique(['user_id', 'badge_id']);
            $table->dropColumn(['year', 'month']);
        });
    }
};
