<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\VocabularyController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\QuizSessionController;
use App\Http\Controllers\Api\BadgeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ヘルスチェック
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'TOEIC Daily API is running',
        'timestamp' => now()->toISOString()
    ]);
});

// 認証API
Route::prefix('auth')->group(function () {
    // メール認証フロー
    Route::post('/send-code', [AuthController::class, 'sendVerificationCode']);
    Route::post('/verify-code', [AuthController::class, 'verifyCode']);
    Route::post('/resend-code', [AuthController::class, 'resendVerificationCode']);

    // 従来の登録（後方互換性）
    Route::post('/register', [AuthController::class, 'register']);
    // ログイン
    Route::post('/login', [AuthController::class, 'login']);
    // Google OAuth コールバック
    Route::post('/google', [AuthController::class, 'googleCallback']);

    // 認証が必要なルート
    Route::middleware('auth:sanctum')->group(function () {
        // ログアウト
        Route::post('/logout', [AuthController::class, 'logout']);
        // 現在のユーザー情報
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// 問題取得API
Route::prefix('questions')->group(function () {
    // 今日の問題を取得
    Route::get('/daily', [QuestionController::class, 'getDaily']);

    // 問題詳細を取得
    Route::get('/{id}', [QuestionController::class, 'show']);

    // 問題一覧を取得（管理者用）
    Route::get('/', [QuestionController::class, 'index']);
});

// 回答記録API（オプショナル認証）
Route::prefix('answers')->middleware('auth:sanctum')->group(function () {
    // 回答を記録（認証ユーザーのみ）
    Route::post('/', [AnswerController::class, 'store']);

    // ユーザーの回答履歴を取得
    Route::get('/history', [AnswerController::class, 'history']);
});

// 学習進捗API
Route::prefix('progress')->group(function () {
    // 学習進捗を取得
    Route::get('/', [ProgressController::class, 'index']);

    // 日次進捗を保存
    Route::post('/', [ProgressController::class, 'store']);
});

// 単語・イディオムAPI
Route::prefix('vocabularies')->group(function () {
    // 単語一覧
    Route::get('/', [VocabularyController::class, 'index']);

    // 単語詳細
    Route::get('/{id}', [VocabularyController::class, 'show']);
});

// 認証が必要なルート
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // 統計API
    Route::prefix('stats')->group(function () {
        Route::get('/dashboard', [StatsController::class, 'dashboard']);
        Route::get('/history', [StatsController::class, 'history']);
        Route::get('/daily', [StatsController::class, 'daily']);
        Route::get('/sessions', [StatsController::class, 'sessions']);
        Route::get('/sessions/{id}', [StatsController::class, 'sessionDetail']);
        Route::get('/calendar', [StatsController::class, 'calendar']);
    });

    // クイズセッションAPI
    Route::prefix('quiz-sessions')->group(function () {
        Route::post('/start', [QuizSessionController::class, 'start']);
        Route::post('/{id}/complete', [QuizSessionController::class, 'complete']);
        Route::get('/current', [QuizSessionController::class, 'current']);
    });

    // バッジAPI
    Route::prefix('badges')->group(function () {
        Route::get('/', [BadgeController::class, 'index']);
        Route::get('/recent', [BadgeController::class, 'recent']);
        Route::get('/history', [BadgeController::class, 'history']);
    });
});
