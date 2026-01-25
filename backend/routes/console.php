<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// 毎日日本時間の8:00に問題を生成（難易度1 - 登録済み語彙からランダムピック）
Schedule::command('questions:generate --difficulty=1 --count=10')
    ->timezone('Asia/Tokyo')
    ->dailyAt('08:00')
    ->name('generate-standard-questions')
    ->withoutOverlapping();

// 毎日日本時間の8:10に問題を生成（難易度2 - 登録済み語彙からランダムピック）
Schedule::command('questions:generate --difficulty=2 --count=10')
    ->timezone('Asia/Tokyo')
    ->dailyAt('08:10')
    ->name('generate-hard-questions')
    ->withoutOverlapping();
