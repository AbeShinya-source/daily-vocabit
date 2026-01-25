<?php

namespace App\Console\Commands;

use App\Mail\DailyQuestionNotificationMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyNotificationsCommand extends Command
{
    /**
     * コマンド名と引数
     *
     * @var string
     */
    protected $signature = 'notifications:send-daily
                            {--dry-run : 実際にメールを送信せずにシミュレーション}';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '本日の問題公開通知メールを送信します';

    /**
     * コマンドを実行
     */
    public function handle(): int
    {
        $this->info("📧 日次通知メールの送信を開始します");

        $today = now()->format('Y年n月j日');

        $users = User::where('email_notification_enabled', true)
            ->whereNotNull('email')
            ->get();

        $this->info("   対象ユーザー数: {$users->count()}人");

        if ($users->isEmpty()) {
            $this->info("✅ 通知対象のユーザーがいません");
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn("⚠️  ドライランモード: 実際のメール送信はスキップされます");
        }

        $sentCount = 0;
        $failedCount = 0;

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            try {
                if (!$this->option('dry-run')) {
                    Mail::to($user->email)->send(
                        new DailyQuestionNotificationMail($user->name, $today)
                    );
                }
                $sentCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $this->newLine();
                $this->error("   送信失敗: {$user->email} - {$e->getMessage()}");
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ 通知メール送信完了");
        $this->table(
            ['項目', '値'],
            [
                ['送信成功', "{$sentCount} 件"],
                ['送信失敗', "{$failedCount} 件"],
            ]
        );

        return self::SUCCESS;
    }
}
