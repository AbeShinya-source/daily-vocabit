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
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('ユーザーID');
            $table->date('date')->comment('学習日(YYYY-MM-DD)');
            $table->enum('type', ['WORD', 'IDIOM'])->comment('種別: WORD=単語, IDIOM=イディオム');
            $table->tinyInteger('difficulty')->comment('難易度');
            $table->integer('total_questions')->comment('その日に解いた問題数');
            $table->integer('correct_count')->comment('正解数');
            $table->integer('score_percent')->comment('正答率(%)');
            $table->integer('study_time')->nullable()->comment('学習時間(秒) - 将来実装');
            $table->timestamps();

            // 同じ日・モード・難易度は1レコードのみ
            $table->unique(['user_id', 'date', 'type', 'difficulty']);
            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
