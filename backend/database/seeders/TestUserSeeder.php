<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuizSession;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        // テストユーザー作成
        $testUser = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'テストユーザー',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        // 過去30日分のセッション履歴を生成
        $this->createSessionHistory($testUser);
    }

    private function createSessionHistory(User $user): void
    {
        $standardQuestions = Question::where('difficulty', 1)->get();
        $hardQuestions = Question::where('difficulty', 2)->get();

        if ($standardQuestions->isEmpty() && $hardQuestions->isEmpty()) {
            return;
        }

        // 過去30日分のデータを生成
        for ($daysAgo = 30; $daysAgo >= 0; $daysAgo--) {
            $date = now()->subDays($daysAgo);

            // 日によってセッション数を変える
            $dayOfWeek = $date->dayOfWeek;
            $doStandard = true;
            $doHard = false;

            // 週末は両方やる確率が高い
            if ($dayOfWeek === 0 || $dayOfWeek === 6) {
                $doHard = rand(1, 100) <= 60;
            } else {
                $doHard = rand(1, 100) <= 30;
            }

            // たまに学習しない日も作る
            if (rand(1, 10) <= 2 && $daysAgo > 6) {
                continue;
            }

            // 連続学習を維持するため、直近7日は必ず学習
            if ($daysAgo <= 6) {
                $doStandard = true;
                $doHard = rand(1, 100) <= 50;
            }

            // Standard セッション
            if ($doStandard && $standardQuestions->isNotEmpty()) {
                $this->createSession($user, $standardQuestions, 1, $date);
            }

            // Hard セッション
            if ($doHard && $hardQuestions->isNotEmpty()) {
                $this->createSession($user, $hardQuestions, 2, $date);
            }
        }
    }

    private function createSession(User $user, $questions, int $difficulty, $date): void
    {
        $startedAt = $date->copy()
            ->setHour(rand(7, 21))
            ->setMinute(rand(0, 59))
            ->setSecond(rand(0, 59));

        $completedAt = $startedAt->copy()->addMinutes(rand(5, 15));

        // 10問をランダムに選択
        $selectedQuestions = $questions->random(min(10, $questions->count()));

        // 正解数を計算（70-90%程度の正答率）
        $correctCount = 0;
        $answers = [];

        foreach ($selectedQuestions as $question) {
            $isCorrect = rand(1, 100) <= rand(70, 90);
            if ($isCorrect) {
                $correctCount++;
            }

            $selectedIndex = $isCorrect
                ? $question->correct_index
                : collect([0, 1, 2, 3])->reject(fn($i) => $i === $question->correct_index)->random();

            $answers[] = [
                'question' => $question,
                'selectedIndex' => $selectedIndex,
                'isCorrect' => $isCorrect,
            ];
        }

        // セッション作成
        $session = QuizSession::create([
            'user_id' => $user->id,
            'difficulty' => $difficulty,
            'quiz_date' => $date->format('Y-m-d'),
            'total_questions' => count($answers),
            'correct_count' => $correctCount,
            'is_completed' => true,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ]);

        // 回答を作成
        foreach ($answers as $index => $answerData) {
            $answeredAt = $startedAt->copy()->addSeconds($index * rand(20, 60));

            UserAnswer::create([
                'user_id' => $user->id,
                'quiz_session_id' => $session->id,
                'question_id' => $answerData['question']->id,
                'selected_index' => $answerData['selectedIndex'],
                'is_correct' => $answerData['isCorrect'],
                'answered_at' => $answeredAt,
            ]);

            // 問題の使用回数を増やす
            $answerData['question']->increment('usage_count');
        }
    }
}
