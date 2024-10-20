<?php

namespace App\Service;

class WordFrequencyAnalyzer
{
    public function findMostFrequentWords(string $text, array $banned): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\s-]/u', ' ', $text);


        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);


        $filteredWords = array_filter($words, function($word) use ($banned) {
            return !in_array($word, $banned) && strlen($word) > 2;
        });


        $wordCounts = array_count_values($filteredWords);


        arsort($wordCounts);


        $frequencies = array_count_values($wordCounts);
        foreach ($frequencies as $frequency => $count) {
            if ($count > 1) {
                $words = array_keys(array_filter($wordCounts, fn($c) => $c === $frequency));
                sort($words);
                foreach ($words as $word) {
                    unset($wordCounts[$word]);
                    $wordCounts[$word] = $frequency;
                }
            }
        }

        return array_slice(array_keys($wordCounts), 0, 3);
    }
}