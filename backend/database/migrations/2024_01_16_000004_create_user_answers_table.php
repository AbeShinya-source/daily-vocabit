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
        Schema::create('user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->comment('ユーザーID(認証実装前はnull)');
            $table->foreignId('question_id')->constrained()->onDelete('cascade')->comment('回答した問題のID');
            $table->tinyInteger('selected_index')->comment('選択した選択肢のインデックス(0-3)');
            $table->boolean('is_correct')->comment('正解したか');
            $table->timestamp('answered_at')->useCurrent()->comment('回答日時');
            $table->timestamps();

            // インデックス
            $table->index(['user_id', 'answered_at']);
            $table->index('question_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_answers');
    }
};
