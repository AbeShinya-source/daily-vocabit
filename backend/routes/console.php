<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 毎日日本時間の7:00に単語を生成（Standard）
Schedule::command('vocabulary:generate --difficulty=1 --count=10')
    ->timezone('Asia/Tokyo')
    ->dailyAt('07:00')
    ->name('generate-standard-vocabularies')
    ->withoutOverlapping();

// 毎日日本時間の7:10に単語を生成（Hard）
Schedule::command('vocabulary:generate --difficulty=2 --count=10')
    ->timezone('Asia/Tokyo')
    ->dailyAt('07:10')
    ->name('generate-hard-vocabularies')
    ->withoutOverlapping();

// 毎日日本時間の7:30にイディオムを生成（Standard）
Schedule::command('vocabulary:generate --difficulty=1 --count=10 --type=IDIOM')
    ->timezone('Asia/Tokyo')
    ->dailyAt('07:30')
    ->name('generate-standard-idioms')
    ->withoutOverlapping();

// 毎日日本時間の7:40にイディオムを生成（Hard）
Schedule::command('vocabulary:generate --difficulty=2 --count=10 --type=IDIOM')
    ->timezone('Asia/Tokyo')
    ->dailyAt('07:40')
    ->name('generate-hard-idioms')
    ->withoutOverlapping();

// 毎日日本時間の8:00に問題を生成（Standard - 単語とイディオムを組み合わせ）
Schedule::command('questions:generate --difficulty=1 --count=10')
    ->timezone('Asia/Tokyo')
    ->dailyAt('08:00')
    ->name('generate-standard-questions')
    ->withoutOverlapping();

// 毎日日本時間の8:00に問題を生成（Hard - 単語とイディオムを組み合わせ）
Schedule::command('questions:generate --difficulty=2 --count=10')
    ->timezone('Asia/Tokyo')
    ->dailyAt('08:00')
    ->name('generate-hard-questions')
    ->withoutOverlapping();
