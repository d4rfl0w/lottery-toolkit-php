<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit;

use Rupcom\LotteryToolkit\Exception\GameMismatchException;
use Rupcom\LotteryToolkit\Exception\ValidationException;

/** @implements \IteratorAggregate<int, Draw> */
final readonly class DrawCollection implements \IteratorAggregate, \Countable
{
    /** @var list<Draw> */
    private array $draws;

    /** @param iterable<Draw> $draws */
    public function __construct(public GameRules $game, iterable $draws = [])
    {
        $items = [];
        $ids = [];
        foreach ($draws as $draw) {
            if (!$game->equals($draw->game)) {
                throw new GameMismatchException('All draws must use the collection game rules.');
            }
            if (isset($ids[$draw->id])) {
                throw new ValidationException("Duplicate draw ID: {$draw->id}.");
            }
            $ids[$draw->id] = true;
            $items[] = $draw;
        }
        usort($items, static fn (Draw $a, Draw $b): int => ($a->date <=> $b->date) ?: strcmp($a->id, $b->id));
        $this->draws = $items;
    }

    /** @return \Traversable<int, Draw> */
    public function getIterator(): \Traversable
    {
        yield from $this->draws;
    }

    public function count(): int
    {
        return count($this->draws);
    }

    public function latest(): ?Draw
    {
        return $this->draws === [] ? null : $this->draws[array_key_last($this->draws)];
    }

    /** @return list<Draw> */
    public function toArray(): array
    {
        return $this->draws;
    }

    public function between(?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): self
    {
        if ($from !== null && $to !== null && $from > $to) {
            throw new ValidationException('The start date must not be after the end date.');
        }

        return new self($this->game, array_filter($this->draws, static fn (Draw $draw): bool =>
            ($from === null || $draw->date >= $from) && ($to === null || $draw->date <= $to)));
    }

    public function last(int $count): self
    {
        if ($count < 1) {
            throw new ValidationException('The number of recent draws must be positive.');
        }

        return new self($this->game, array_slice($this->draws, -$count));
    }
}
