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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocabulary_id')->constrained()->onDelete('cascade')->comment('出題される単語・イディオムのID');
            $table->enum('type', ['WORD', 'IDIOM'])->comment('種別(vocabulary.typeのコピー)');
            $table->tinyInteger('difficulty')->comment('難易度(vocabulary.difficultyのコピー)');
            $table->text('question_text')->comment('問題文');
            $table->json('choices')->comment('選択肢(JSON配列: ["A","B","C","D"])');
            $table->tinyInteger('correct_index')->comment('正解のインデックス(0-3)');
            $table->text('explanation')->comment('解説文');
            $table->date('generated_date')->comment('生成日(YYYY-MM-DD)');
            $table->boolean('is_active')->default(true)->comment('使用可能かどうか');
            $table->integer('usage_count')->default(0)->comment('出題回数');
            $table->timestamps();

            // インデックス
            $table->index(['generated_date', 'type', 'difficulty', 'is_active']);
            $table->index('vocabulary_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
