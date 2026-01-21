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
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('title_en'); // テーマ名（英語）
            $table->string('title_ja'); // テーマ名（日本語）
            $table->text('description')->nullable(); // テーマの説明
            $table->date('date')->unique(); // 適用日（ユニーク）
            $table->boolean('is_active')->default(true); // 有効フラグ
            $table->timestamps();

            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
