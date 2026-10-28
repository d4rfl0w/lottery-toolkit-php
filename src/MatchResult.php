<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit;

use Rupcom\LotteryToolkit\Exception\ValidationException;

/** One bet's matches, with zero-based bet index. No payout inference. */
final readonly class MatchResult implements \JsonSerializable
{
    /** @param array<string, list<int>> $matchedNumbers */
    public function __construct(
        public int $betIndex,
        public string $drawId,
        public array $matchedNumbers,
    ) {
        if ($betIndex < 0 || trim($drawId) === '') {
            throw new ValidationException('A match requires a non-negative bet index and a draw ID.');
        }
    }

    public function count(string $pool = 'main'): int
    {
        return count($this->matchedNumbers[$pool] ?? throw new ValidationException("Unknown match pool: $pool."));
    }

    public function total(): int
    {
        return array_sum(array_map(count(...), $this->matchedNumbers));
    }

    /** @return array{betIndex: int, drawId: string, matchedNumbers: array<string, list<int>>, counts: array<string, int>, total: int} */
    public function jsonSerialize(): array
    {
        return ['betIndex' => $this->betIndex, 'drawId' => $this->drawId, 'matchedNumbers' => $this->matchedNumbers,
            'counts' => array_map(count(...), $this->matchedNumbers), 'total' => $this->total()];
    }
}
