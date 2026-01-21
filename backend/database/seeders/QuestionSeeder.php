<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\Vocabulary;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->format('Y-m-d');

        // 単語に対する問題を生成
        $vocabularies = Vocabulary::all();

        foreach ($vocabularies as $vocab) {
            $this->createQuestionForVocabulary($vocab, $today);
        }
    }

    private function createQuestionForVocabulary(Vocabulary $vocab, string $date): void
    {
        // 既存の問題があればスキップ
        if (Question::where('vocabulary_id', $vocab->id)->where('generated_date', $date)->exists()) {
            return;
        }

        $questionData = $this->generateQuestion($vocab);

        Question::create([
            'vocabulary_id' => $vocab->id,
            'type' => $vocab->type,
            'difficulty' => $vocab->difficulty,
            'question_text' => $questionData['question_text'],
            'question_translation' => $questionData['question_translation'],
            'choices' => $questionData['choices'],
            'correct_index' => $questionData['correct_index'],
            'explanation' => $questionData['explanation'],
            'generated_date' => $date,
            'is_active' => true,
            'usage_count' => 0,
        ]);
    }

    private function generateQuestion(Vocabulary $vocab): array
    {
        $questions = $this->getQuestionsForWord($vocab->word);

        if ($questions) {
            return $questions;
        }

        // デフォルトの問題形式
        return [
            'question_text' => "What is the meaning of \"{$vocab->word}\"?",
            'question_translation' => "「{$vocab->word}」の意味は何ですか？",
            'choices' => [
                $vocab->meaning,
                '間違った選択肢1',
                '間違った選択肢2',
                '間違った選択肢3',
            ],
            'correct_index' => 0,
            'explanation' => "{$vocab->word}は「{$vocab->meaning}」という意味です。例文: {$vocab->example_sentence}",
        ];
    }

    private function getQuestionsForWord(string $word): ?array
    {
        $questions = [
            // WORD - Difficulty 1
            'implement' => [
                'question_text' => 'The company plans to ______ the new software system next month.',
                'question_translation' => '会社は来月、新しいソフトウェアシステムを______予定です。',
                'choices' => ['implement', 'eliminate', 'postpone', 'abandon'],
                'correct_index' => 0,
                'explanation' => 'implement は「実行する、実施する」という意味です。文脈から、新しいシステムを導入・実施するという意味が適切です。',
            ],
            'deadline' => [
                'question_text' => 'We must complete the report before the ______ on Friday.',
                'question_translation' => '金曜日の______までにレポートを完成させなければなりません。',
                'choices' => ['deadline', 'headline', 'guideline', 'timeline'],
                'correct_index' => 0,
                'explanation' => 'deadline は「締め切り、期限」という意味です。レポートの提出期限を指しています。',
            ],
            'budget' => [
                'question_text' => 'The marketing department exceeded its ______ by 15%.',
                'question_translation' => 'マーケティング部門は______を15%超過しました。',
                'choices' => ['budget', 'target', 'quota', 'limit'],
                'correct_index' => 0,
                'explanation' => 'budget は「予算」という意味です。部門が予算を超過したことを表しています。',
            ],
            'collaborate' => [
                'question_text' => 'Our team will ______ with the design department on this project.',
                'question_translation' => '私たちのチームはこのプロジェクトでデザイン部門と______します。',
                'choices' => ['collaborate', 'compete', 'conflict', 'compare'],
                'correct_index' => 0,
                'explanation' => 'collaborate は「協力する、共同で行う」という意味です。チーム間の協力を表しています。',
            ],
            'revenue' => [
                'question_text' => 'The company\'s annual ______ increased by 20% this year.',
                'question_translation' => '会社の年間______は今年20%増加しました。',
                'choices' => ['revenue', 'expense', 'debt', 'loss'],
                'correct_index' => 0,
                'explanation' => 'revenue は「収益、収入」という意味です。会社の収入が増加したことを表しています。',
            ],
            'negotiate' => [
                'question_text' => 'We need to ______ the terms of the contract with the supplier.',
                'question_translation' => 'サプライヤーと契約条件を______する必要があります。',
                'choices' => ['negotiate', 'terminate', 'violate', 'ignore'],
                'correct_index' => 0,
                'explanation' => 'negotiate は「交渉する」という意味です。契約条件について話し合うことを指しています。',
            ],
            'efficient' => [
                'question_text' => 'The new process is more ______ than the previous one.',
                'question_translation' => '新しいプロセスは以前のものより______です。',
                'choices' => ['efficient', 'expensive', 'difficult', 'complicated'],
                'correct_index' => 0,
                'explanation' => 'efficient は「効率的な」という意味です。新しいプロセスの効率性を表しています。',
            ],
            'schedule' => [
                'question_text' => 'Please check your ______ and let me know your availability.',
                'question_translation' => '______を確認して、空いている時間を教えてください。',
                'choices' => ['schedule', 'document', 'account', 'receipt'],
                'correct_index' => 0,
                'explanation' => 'schedule は「予定、スケジュール」という意味です。',
            ],
            'proposal' => [
                'question_text' => 'The board approved the ______ for the new product launch.',
                'question_translation' => '取締役会は新製品発売の______を承認しました。',
                'choices' => ['proposal', 'complaint', 'rejection', 'warning'],
                'correct_index' => 0,
                'explanation' => 'proposal は「提案、企画書」という意味です。',
            ],
            'merchandise' => [
                'question_text' => 'The store received a new shipment of ______ yesterday.',
                'question_translation' => '店舗は昨日、新しい______の出荷を受け取りました。',
                'choices' => ['merchandise', 'furniture', 'equipment', 'machinery'],
                'correct_index' => 0,
                'explanation' => 'merchandise は「商品、製品」という意味です。',
            ],

            // WORD - Difficulty 2
            'acquisition' => [
                'question_text' => 'The ______ of the tech startup was valued at $500 million.',
                'question_translation' => 'そのテックスタートアップの______は5億ドルと評価されました。',
                'choices' => ['acquisition', 'bankruptcy', 'dissolution', 'liquidation'],
                'correct_index' => 0,
                'explanation' => 'acquisition は「買収、取得」という意味です。企業の買収額を表しています。',
            ],
            'stipulate' => [
                'question_text' => 'The agreement ______ that all payments must be made within 30 days.',
                'question_translation' => '契約書には、すべての支払いは30日以内に行わなければならないと______されています。',
                'choices' => ['stipulates', 'suggests', 'implies', 'hints'],
                'correct_index' => 0,
                'explanation' => 'stipulate は「規定する、明記する」という意味です。契約の条項を明確に定めることを指します。',
            ],
            'consolidate' => [
                'question_text' => 'The company decided to ______ its three regional offices into one.',
                'question_translation' => '会社は3つの地域オフィスを1つに______することを決定しました。',
                'choices' => ['consolidate', 'separate', 'distribute', 'scatter'],
                'correct_index' => 0,
                'explanation' => 'consolidate は「統合する」という意味です。複数のオフィスを一つにまとめることを表しています。',
            ],
            'leverage' => [
                'question_text' => 'We should ______ our existing customer relationships to expand sales.',
                'question_translation' => '売上を拡大するために、既存の顧客関係を______すべきです。',
                'choices' => ['leverage', 'abandon', 'ignore', 'neglect'],
                'correct_index' => 0,
                'explanation' => 'leverage は「活用する」という意味です。既存のリソースを有効に使うことを指します。',
            ],
            'mitigate' => [
                'question_text' => 'The insurance policy helps ______ the financial risks.',
                'question_translation' => '保険は財務リスクを______するのに役立ちます。',
                'choices' => ['mitigate', 'increase', 'amplify', 'maximize'],
                'correct_index' => 0,
                'explanation' => 'mitigate は「軽減する、緩和する」という意味です。リスクを減らすことを表しています。',
            ],
            'scrutinize' => [
                'question_text' => 'The auditor will ______ all financial records carefully.',
                'question_translation' => '監査人はすべての財務記録を注意深く______します。',
                'choices' => ['scrutinize', 'overlook', 'ignore', 'skip'],
                'correct_index' => 0,
                'explanation' => 'scrutinize は「精査する、詳しく調べる」という意味です。',
            ],
            'reimbursement' => [
                'question_text' => 'Employees can submit travel expenses for ______.',
                'question_translation' => '従業員は出張費の______を申請できます。',
                'choices' => ['reimbursement', 'punishment', 'dismissal', 'penalty'],
                'correct_index' => 0,
                'explanation' => 'reimbursement は「払い戻し、弁償」という意味です。',
            ],
            'contingency' => [
                'question_text' => 'We have a ______ plan in case the main system fails.',
                'question_translation' => 'メインシステムが故障した場合の______計画があります。',
                'choices' => ['contingency', 'permanent', 'regular', 'standard'],
                'correct_index' => 0,
                'explanation' => 'contingency は「不測の事態、緊急時対応」という意味です。',
            ],
            'proprietary' => [
                'question_text' => 'This is ______ technology that cannot be shared with competitors.',
                'question_translation' => 'これは競合他社と共有できない______技術です。',
                'choices' => ['proprietary', 'public', 'common', 'shared'],
                'correct_index' => 0,
                'explanation' => 'proprietary は「専有の、独自の」という意味です。',
            ],
            'unprecedented' => [
                'question_text' => 'The company achieved ______ growth during the pandemic.',
                'question_translation' => '会社はパンデミック中に______成長を達成しました。',
                'choices' => ['unprecedented', 'typical', 'normal', 'expected'],
                'correct_index' => 0,
                'explanation' => 'unprecedented は「前例のない」という意味です。',
            ],

            // IDIOM - Difficulty 1
            'on the same page' => [
                'question_text' => 'Let\'s make sure we\'re all ______ before starting the project.',
                'question_translation' => 'プロジェクトを始める前に、全員が______であることを確認しましょう。',
                'choices' => ['on the same page', 'under the weather', 'over the moon', 'behind the times'],
                'correct_index' => 0,
                'explanation' => 'on the same page は「同じ認識を持っている」という意味のイディオムです。',
            ],
            'get the ball rolling' => [
                'question_text' => 'We need to ______ on this initiative as soon as possible.',
                'question_translation' => 'この取り組みをできるだけ早く______必要があります。',
                'choices' => ['get the ball rolling', 'drop the ball', 'play ball', 'have a ball'],
                'correct_index' => 0,
                'explanation' => 'get the ball rolling は「物事を始める」という意味のイディオムです。',
            ],
            'touch base' => [
                'question_text' => 'I\'ll ______ with you next week to discuss the progress.',
                'question_translation' => '来週、進捗について話し合うために______します。',
                'choices' => ['touch base', 'hit bottom', 'reach out', 'stand tall'],
                'correct_index' => 0,
                'explanation' => 'touch base は「連絡を取る、確認する」という意味のイディオムです。',
            ],
            'keep someone in the loop' => [
                'question_text' => 'Please ______ about any changes to the schedule.',
                'question_translation' => 'スケジュールの変更があれば______ください。',
                'choices' => ['keep me in the loop', 'leave me out', 'cut me off', 'shut me down'],
                'correct_index' => 0,
                'explanation' => 'keep someone in the loop は「情報を共有する」という意味のイディオムです。',
            ],
            'call it a day' => [
                'question_text' => 'We\'ve been working for 10 hours. Let\'s ______.',
                'question_translation' => '10時間も働いています。______しましょう。',
                'choices' => ['call it a day', 'make my day', 'save the day', 'day by day'],
                'correct_index' => 0,
                'explanation' => 'call it a day は「仕事を切り上げる」という意味のイディオムです。',
            ],

            // IDIOM - Difficulty 2
            'cut corners' => [
                'question_text' => 'We cannot afford to ______ on safety measures.',
                'question_translation' => '安全対策で______するわけにはいきません。',
                'choices' => ['cut corners', 'turn corners', 'round corners', 'paint corners'],
                'correct_index' => 0,
                'explanation' => 'cut corners は「手抜きをする」という意味のイディオムです。',
            ],
            'back to the drawing board' => [
                'question_text' => 'The proposal was rejected, so it\'s ______.',
                'question_translation' => '提案は却下されたので、______です。',
                'choices' => ['back to the drawing board', 'ahead of the game', 'behind the scenes', 'across the board'],
                'correct_index' => 0,
                'explanation' => 'back to the drawing board は「最初からやり直す」という意味のイディオムです。',
            ],
            'bring to the table' => [
                'question_text' => 'What unique skills do you ______ for this position?',
                'question_translation' => 'このポジションにどのようなユニークなスキルを______ますか？',
                'choices' => ['bring to the table', 'put on the shelf', 'throw in the towel', 'sweep under the rug'],
                'correct_index' => 0,
                'explanation' => 'bring to the table は「提供する、貢献する」という意味のイディオムです。',
            ],
            'go the extra mile' => [
                'question_text' => 'Our customer service team always ______ for clients.',
                'question_translation' => 'カスタマーサービスチームは常にお客様のために______します。',
                'choices' => ['goes the extra mile', 'takes a back seat', 'plays it safe', 'keeps a low profile'],
                'correct_index' => 0,
                'explanation' => 'go the extra mile は「期待以上のことをする」という意味のイディオムです。',
            ],
            'think outside the box' => [
                'question_text' => 'To solve this problem, we need to ______.',
                'question_translation' => 'この問題を解決するには、______する必要があります。',
                'choices' => ['think outside the box', 'stay inside the lines', 'follow the rules', 'play by the book'],
                'correct_index' => 0,
                'explanation' => 'think outside the box は「型にはまらない考え方をする」という意味のイディオムです。',
            ],
        ];

        return $questions[$word] ?? null;
    }
}
