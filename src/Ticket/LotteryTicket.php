<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Ticket;

use Rupcom\LotteryToolkit\Exception\ValidationException;
use Rupcom\LotteryToolkit\Game\GameRules;

final readonly class LotteryTicket
{
    /** @var non-empty-list<array<string, list<int>>> */
    public array $bets;

    /** @param array<array-key, mixed> $bets */
    public function __construct(public GameRules $game, array $bets)
    {
        if ($bets === [] || !array_is_list($bets)) {
            throw new ValidationException('A ticket requires a non-empty list of bets.');
        }
        $validated = [];
        foreach ($bets as $index => $bet) {
            if (!is_array($bet)) {
                throw new ValidationException("Bet $index must be a map of number pools.");
            }
            $validated[] = $game->validate($bet);
        }
        $this->bets = $validated;
    }
}
