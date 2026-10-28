<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit;

use Rupcom\LotteryToolkit\Exception\ValidationException;

final readonly class GameRegistry
{
    /** @var array<string, GameRules> */
    private array $games;

    /** @param iterable<GameRules> $games Custom definitions override built-in IDs intentionally. */
    public function __construct(iterable $games = [])
    {
        $indexed = [];
        foreach ([GameRules::lotto(), GameRules::miniLotto(), GameRules::eurojackpot(), ...$games] as $game) {
            $indexed[$game->id] = $game;
        }
        $this->games = $indexed;
    }

    public function get(string $id): GameRules
    {
        return $this->games[$id] ?? throw new ValidationException("Unknown game: $id.");
    }

    /** @return array<string, GameRules> */
    public function all(): array
    {
        return $this->games;
    }
}
