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
        // MySQLでは外部キー制約に使用されているインデックスを直接削除できないため、
        // 先に外部キー制約を削除してからインデックスを削除する
        Schema::table('user_badges', function (Blueprint $table) {
            // 外部キー制約を一時的に削除
            $table->dropForeign(['user_id']);
            $table->dropForeign(['badge_id']);
            // ユニーク制約を削除
            $table->dropUnique(['user_id', 'badge_id']);
            // 新しいユニーク制約を追加
            $table->unique(['user_id', 'badge_id', 'year', 'month']);
            // 外部キー制約を再追加
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['badge_id']);
            $table->dropUnique(['user_id', 'badge_id', 'year', 'month']);
            $table->unique(['user_id', 'badge_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('badge_id')->references('id')->on('badges')->onDelete('cascade');
            $table->dropColumn(['year', 'month']);
        });
    }
};
