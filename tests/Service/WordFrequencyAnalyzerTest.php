<?php

namespace App\Tests\Service;

use App\Service\WordFrequencyAnalyzer;
use PHPUnit\Framework\TestCase;

class WordFrequencyAnalyzerTest extends TestCase
{
    private WordFrequencyAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new WordFrequencyAnalyzer();
    }

    public function testFindMostFrequentWords(): void
    {
        $text = "The quick brown fox jumps over the lazy dog. The fox is quick and brown.";
        $banned = ['the', 'is', 'and', 'over'];

        $result = $this->analyzer->findMostFrequentWords($text, $banned);

        $this->assertCount(3, $result);
        $this->assertEqualsCanonicalizing(['fox', 'quick', 'brown'], $result);
    }

    public function testFindMostFrequentWordsWithLessThanThreeWords(): void
    {
        $text = "Hello world. Hello there.";
        $banned = ['there'];

        $result = $this->analyzer->findMostFrequentWords($text, $banned);

        $this->assertCount(2, $result);
        $this->assertEqualsCanonicalizing(['hello', 'world'], $result);
    }
}