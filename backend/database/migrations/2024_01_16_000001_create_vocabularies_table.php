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
        Schema::create('vocabularies', function (Blueprint $table) {
            $table->id();
            $table->string('word')->unique()->comment('単語またはイディオム本体');
            $table->enum('type', ['WORD', 'IDIOM'])->comment('種別: WORD=単語, IDIOM=イディオム');
            $table->tinyInteger('difficulty')->comment('難易度: 1=基礎(600点), 2=上級(800点), 3=超上級');
            $table->string('meaning')->comment('日本語の意味');
            $table->string('part_of_speech')->nullable()->comment('品詞(動詞、名詞など)');
            $table->text('example_sentence')->nullable()->comment('例文');
            $table->string('synonym')->nullable()->comment('類義語(カンマ区切り)');
            $table->string('antonym')->nullable()->comment('対義語(カンマ区切り)');
            $table->integer('frequency')->default(0)->comment('TOEIC頻出度(高いほど重要)');
            $table->string('tags')->nullable()->comment('タグ(カンマ区切り: ビジネス,会議,交渉)');
            $table->timestamps();

            // インデックス
            $table->index(['type', 'difficulty']);
            $table->index('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vocabularies');
    }
};
