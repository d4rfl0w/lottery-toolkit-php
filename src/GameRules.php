<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit;

use Rupcom\LotteryToolkit\Exception\ValidationException;

final readonly class GameRules
{
    /** @var non-empty-array<string, NumberPool> */
    public array $pools;

    /** @param array<array-key, NumberPool> $pools */
    public function __construct(public string $id, array $pools)
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/D', $id) || $pools === [] || !array_is_list($pools)) {
            throw new ValidationException('A game requires a lowercase identifier and a non-empty list of number pools.');
        }
        $indexed = [];
        foreach ($pools as $pool) {
            if (isset($indexed[$pool->name])) {
                throw new ValidationException("Duplicate pool name: {$pool->name}.");
            }
            $indexed[$pool->name] = $pool;
        }
        ksort($indexed);
        $this->pools = $indexed;
    }

    public static function lotto(): self
    {
        return new self('lotto', [new NumberPool('main', 6, 1, 49)]);
    }

    public static function miniLotto(): self
    {
        return new self('mini_lotto', [new NumberPool('main', 5, 1, 42)]);
    }

    public static function eurojackpot(): self
    {
        return new self('eurojackpot', [new NumberPool('main', 5, 1, 50), new NumberPool('euro', 2, 1, 12)]);
    }

    public function pool(string $name): NumberPool
    {
        return $this->pools[$name] ?? throw new ValidationException("Unknown pool '$name' for game {$this->id}.");
    }

    /** @param array<array-key, mixed> $numbers
     * @return array<string, list<int>> */
    public function validate(array $numbers): array
    {
        if (count($numbers) !== count($this->pools) || array_diff_key($numbers, $this->pools) !== []) {
            throw new ValidationException('Expected exactly these pools: '.implode(', ', array_keys($this->pools)).'.');
        }
        $validated = [];
        foreach ($this->pools as $name => $pool) {
            if (!is_array($numbers[$name])) {
                throw new ValidationException("Pool $name must be an array of integers.");
            }
            $validated[$name] = $pool->validate($numbers[$name]);
        }

        return $validated;
    }

    public function equals(self $other): bool
    {
        return $this == $other;
    }
}
