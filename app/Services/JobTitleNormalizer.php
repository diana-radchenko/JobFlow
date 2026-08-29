<?php

namespace App\Services;

class JobTitleNormalizer
{
    private const STOP_WORDS = [
        'summer', 'camp', 'seasonal', 'remote', 'online', 'onsite', 'hybrid',
        'part', 'time', 'full', 'temporary', 'contract', 'the', 'and', 'for',
        'with', 'a', 'an',
    ];

    private const SYNONYMS = [
        'programming' => 'coding',
        'programmer' => 'coding',
        'teacher' => 'instructor',
        'teachers' => 'instructor',
        'developers' => 'developer',
        'engineers' => 'engineer',
        'assistants' => 'assistant',
        'instructors' => 'instructor',
    ];

    /** @return list<string> */
    public function keywords(string $title): array
    {
        $tokens = preg_split('/[^a-z0-9]+/', strtolower($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_map(function (string $token) {
            $token = $this->singular($token);

            return self::SYNONYMS[$token] ?? $token;
        }, $tokens);

        return array_values(array_unique(array_filter($tokens, fn (string $token) => strlen($token) > 2 && ! in_array($token, self::STOP_WORDS, true))));
    }

    public function similarity(string $left, string $right): float
    {
        $leftTokens = $this->keywords($left);
        $rightTokens = $this->keywords($right);
        if ($leftTokens === [] || $rightTokens === []) {
            return 0;
        }

        $intersection = array_intersect($leftTokens, $rightTokens);

        return count($intersection) / min(count($leftTokens), count($rightTokens));
    }

    public function comparable(string $left, string $right): bool
    {
        return $this->similarity($left, $right) >= 0.6;
    }

    /** @return list<string> */
    public function sharedKeywords(string $left, string $right): array
    {
        return array_values(array_intersect($this->keywords($left), $this->keywords($right)));
    }

    private function singular(string $token): string
    {
        if (str_ends_with($token, 'ies') && strlen($token) > 4) {
            return substr($token, 0, -3).'y';
        }

        if (str_ends_with($token, 's') && ! str_ends_with($token, 'ss') && strlen($token) > 3) {
            return substr($token, 0, -1);
        }

        return $token;
    }
}
