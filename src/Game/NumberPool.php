<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Game;

use Rupcom\LotteryToolkit\Exception\ValidationException;

/** Rules for one unordered pool; limits keep enumeration and pair analysis bounded. */
final readonly class NumberPool
{
    /** @var positive-int */
    public int $count;

    public function __construct(
        public string $name,
        int $count,
        public int $min,
        public int $max,
        public bool $allowDuplicates = false,
    ) {
        if (!preg_match('/^[a-z][a-z0-9_]*$/D', $name) || in_array($name, ['id', 'date'], true)) {
            throw new ValidationException('Pool names must be lowercase identifiers other than "id" and "date".');
        }
        if ($min < 1 || $max < $min || $max > 1_000_000 || $max - $min > 9_999) {
            throw new ValidationException("Pool $name requires 1 <= min <= max <= 1000000 and at most 10000 possible numbers.");
        }
        if ($count < 1 || $count > 100 || (!$allowDuplicates && $count > $max - $min + 1)) {
            throw new ValidationException("Pool $name requires 1 to 100 picks, within the available range when duplicates are disabled.");
        }
        $this->count = $count;
    }

    /** @param array<array-key, mixed> $numbers
     * @return list<int> */
    public function validate(array $numbers): array
    {
        if (!array_is_list($numbers) || count($numbers) !== $this->count) {
            throw new ValidationException("Pool {$this->name} requires a list of exactly {$this->count} integers.");
        }
        $validated = [];
        foreach ($numbers as $number) {
            if (!is_int($number) || $number < $this->min || $number > $this->max) {
                throw new ValidationException("Pool {$this->name} requires integers from {$this->min} to {$this->max}.");
            }
            $validated[] = $number;
        }
        if (!$this->allowDuplicates && count(array_unique($validated)) !== $this->count) {
            throw new ValidationException("Pool {$this->name} does not allow duplicate numbers.");
        }
        sort($validated, SORT_NUMERIC);

        return $validated;
    }
}
