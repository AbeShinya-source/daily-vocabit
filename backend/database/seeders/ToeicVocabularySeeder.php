<?php

namespace Database\Seeders;

use App\Models\Vocabulary;
use Illuminate\Database\Seeder;

class ToeicVocabularySeeder extends Seeder
{
    public function run(): void
    {
        $dataPath = database_path('data');

        // 単語データを読み込み
        $wordsFile = $dataPath . '/toeic_words.json';
        if (file_exists($wordsFile)) {
            $words = json_decode(file_get_contents($wordsFile), true);
            $this->importVocabularies($words, 'WORD');
        }

        // イディオムデータを読み込み
        $idiomsFile = $dataPath . '/toeic_idioms.json';
        if (file_exists($idiomsFile)) {
            $idioms = json_decode(file_get_contents($idiomsFile), true);
            $this->importVocabularies($idioms, 'IDIOM');
        }
    }

    private function importVocabularies(array $items, string $type): void
    {
        $count = 0;
        foreach ($items as $item) {
            Vocabulary::updateOrCreate(
                ['word' => $item['word'], 'type' => $type],
                [
                    'difficulty' => $item['difficulty'],
                    'meaning' => $item['meaning'],
                    'part_of_speech' => $item['part_of_speech'] ?? null,
                    'frequency' => $item['frequency'] ?? 3,
                ]
            );
            $count++;
        }
        $this->command->info("{$type}: {$count}件インポートしました");
    }
}
