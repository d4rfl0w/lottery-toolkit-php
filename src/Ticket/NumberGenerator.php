<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Ticket;

use Random\Engine\Secure;
use Random\Randomizer;
use Rupcom\LotteryToolkit\Exception\ValidationException;
use Rupcom\LotteryToolkit\Game\GameRules;

final class NumberGenerator
{
    private readonly Randomizer $randomizer;

    /** Default engine is cryptographically secure; inject a seeded Randomizer only for reproducible tests. */
    public function __construct(?Randomizer $randomizer = null)
    {
        $this->randomizer = $randomizer ?? new Randomizer(new Secure());
    }

    public function generate(GameRules $game, int $bets = 1): LotteryTicket
    {
        if ($bets < 1 || $bets > 10_000) {
            throw new ValidationException('Generate between 1 and 10000 bets per call.');
        }
        $lines = [];
        for ($i = 0; $i < $bets; ++$i) {
            $line = [];
            foreach ($game->pools as $name => $pool) {
                $numbers = [];
                if ($pool->allowDuplicates) {
                    for ($j = 0; $j < $pool->count; ++$j) {
                        $numbers[] = $this->randomizer->getInt($pool->min, $pool->max);
                    }
                } else {
                    $range = range($pool->min, $pool->max);
                    foreach ($this->randomizer->pickArrayKeys($range, $pool->count) as $key) {
                        $numbers[] = $range[$key];
                    }
                }
                $line[$name] = $numbers;
            }
            $lines[] = $line;
        }

        return new LotteryTicket($game, $lines);
    }
}
