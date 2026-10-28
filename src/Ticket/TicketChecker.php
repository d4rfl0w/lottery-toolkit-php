<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Ticket;

use Rupcom\LotteryToolkit\Draw\Draw;
use Rupcom\LotteryToolkit\Exception\GameMismatchException;

final class TicketChecker
{
    /** @return non-empty-list<MatchResult> */
    public function check(LotteryTicket $ticket, Draw $draw): array
    {
        if (!$ticket->game->equals($draw->game)) {
            throw new GameMismatchException('Ticket and draw must use identical game rules.');
        }

        $drawCounts = array_map(array_count_values(...), $draw->numbers);
        $results = [];
        foreach ($ticket->bets as $index => $bet) {
            $results[] = new MatchResult($index, $draw->id, $this->matchBet($bet, $drawCounts));
        }

        return $results;
    }

    /**
     * @param array<string, list<int>> $bet
     * @param array<string, array<int, positive-int>> $drawCounts
     * @return array<string, list<int>>
     */
    private function matchBet(array $bet, array $drawCounts): array
    {
        $matches = [];
        foreach ($bet as $pool => $numbers) {
            $matches[$pool] = $this->matchPool($numbers, $drawCounts[$pool]);
        }

        return $matches;
    }

    /**
     * Each occurrence can match once per bet; the counts are a local copy.
     *
     * @param list<int> $numbers
     * @param array<int, positive-int> $available
     * @return list<int>
     */
    private function matchPool(array $numbers, array $available): array
    {
        $matches = [];
        foreach ($numbers as $number) {
            if (($available[$number] ?? 0) === 0) {
                continue;
            }

            $matches[] = $number;
            --$available[$number];
        }

        return $matches;
    }
}
