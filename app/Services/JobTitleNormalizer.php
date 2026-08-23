<?php

namespace App\Services;

class JobTitleNormalizer
{
    private const STOP_WORDS = ['summer', 'camp', 'seasonal', 'remote', 'part', 'time', 'full', 'temporary', 'the', 'and', 'for', 'a', 'an'];
    private const SYNONYMS = ['programming' => 'coding', 'developer' => 'engineer', 'teacher' => 'instructor'];

    /** @return list<string> */
    public function keywords(string $title): array
    {
        $tokens = preg_split('/[^a-z0-9]+/', strtolower($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_map(fn (string $token) => self::SYNONYMS[$token] ?? $token, $tokens);

        return array_values(array_unique(array_filter($tokens, fn (string $token) => strlen($token) > 2 && ! in_array($token, self::STOP_WORDS, true))));
    }

    public function similarity(string $left, string $right): float
    {
        $leftTokens = $this->keywords($left);
        $rightTokens = $this->keywords($right);
        if ($leftTokens === [] || $rightTokens === []) return 0;
        $intersection = array_intersect($leftTokens, $rightTokens);

        return count($intersection) / count(array_unique(array_merge($leftTokens, $rightTokens)));
    }
}
