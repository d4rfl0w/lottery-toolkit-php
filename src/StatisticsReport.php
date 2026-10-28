<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit;

use Rupcom\LotteryToolkit\Exception\ValidationException;

/** Immutable descriptive statistics; all counts refer only to the selected history. */
final readonly class StatisticsReport implements \JsonSerializable
{
    /**
     * @param array<int, int> $frequency Occurrence count including zeroes.
     * @param array<int, int|null> $drawsSinceLastOccurrence Null means unseen, zero means latest draw.
     * @param array<string, int> $oddEvenDistribution Keys are "odd:even", values count draws.
     * @param array<int, int> $sumDistribution Keys are sums, values count draws.
     * @param array<string, int> $pairs Keys are "smaller:larger", values count draws containing the pair.
     * @internal Obtain reports from StatisticsAnalyzer; constructor layout is not a compatibility contract.
     */
    public function __construct(
        public string $gameId,
        public string $pool,
        public int $drawCount,
        public array $frequency,
        public array $drawsSinceLastOccurrence,
        public array $oddEvenDistribution,
        public array $sumDistribution,
        public array $pairs,
    ) {
    }

    /** @return array<int, int> Number => occurrences, ordered by count then ascending number. */
    public function ranked(FrequencyOrder $order = FrequencyOrder::MostFrequent, int $limit = 10): array
    {
        $this->validateLimit($limit);
        $frequencies = $this->frequency;
        uksort($frequencies, fn (int $a, int $b): int =>
            ($order === FrequencyOrder::MostFrequent
                ? $frequencies[$b] <=> $frequencies[$a]
                : $frequencies[$a] <=> $frequencies[$b]) ?: $a <=> $b);

        return array_slice($frequencies, 0, $limit, true);
    }

    /** @return array<int, int> */
    public function mostFrequent(int $limit = 10): array
    {
        return $this->ranked(FrequencyOrder::MostFrequent, $limit);
    }

    /** @return array<int, int> */
    public function leastFrequent(int $limit = 10): array
    {
        return $this->ranked(FrequencyOrder::LeastFrequent, $limit);
    }

    /** @return array<string, int> Pair => draw count; ties compare the two numbers numerically. */
    public function mostFrequentPairs(int $limit = 10): array
    {
        $this->validateLimit($limit);
        $pairs = $this->pairs;
        uksort($pairs, static fn (string $a, string $b): int => ($pairs[$b] <=> $pairs[$a])
            ?: array_map(intval(...), explode(':', $a)) <=> array_map(intval(...), explode(':', $b)));

        return array_slice($pairs, 0, $limit, true);
    }

    /** @return array<string, mixed> JSON histogram maps are always objects, including empty maps. */
    public function jsonSerialize(): array
    {
        return ['gameId' => $this->gameId, 'pool' => $this->pool, 'drawCount' => $this->drawCount,
            'frequency' => (object) $this->frequency, 'drawsSinceLastOccurrence' => (object) $this->drawsSinceLastOccurrence,
            'oddEvenDistribution' => (object) $this->oddEvenDistribution,
            'sumDistribution' => (object) $this->sumDistribution, 'pairs' => (object) $this->pairs];
    }

    private function validateLimit(int $limit): void
    {
        if ($limit < 1) {
            throw new ValidationException('Ranking limit must be positive.');
        }
    }
}
