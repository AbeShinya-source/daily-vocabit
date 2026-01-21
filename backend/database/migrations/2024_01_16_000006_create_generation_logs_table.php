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
        Schema::create('generation_logs', function (Blueprint $table) {
            $table->id();
            $table->date('generated_date')->comment('生成日(YYYY-MM-DD)');
            $table->enum('type', ['WORD', 'IDIOM'])->comment('種別: WORD=単語, IDIOM=イディオム');
            $table->tinyInteger('difficulty')->comment('難易度');
            $table->integer('questions_count')->comment('生成した問題数');
            $table->string('ai_model')->comment('使用したAIモデル(gpt-4, claude-3.5など)');
            $table->integer('prompt_tokens')->nullable()->comment('プロンプトトークン数');
            $table->integer('completion_tokens')->nullable()->comment('補完トークン数');
            $table->decimal('total_cost', 10, 6)->nullable()->comment('APIコスト($)');
            $table->enum('status', ['SUCCESS', 'PARTIAL', 'FAILED'])->default('SUCCESS')->comment('ステータス');
            $table->text('error_message')->nullable()->comment('エラー時のメッセージ');
            $table->timestamps();

            // インデックス
            $table->index('generated_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generation_logs');
    }
};
