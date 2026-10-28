<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit;

use Rupcom\LotteryToolkit\Exception\GameMismatchException;

final class TicketChecker
{
    /** @return non-empty-list<MatchResult> */
    public function check(LotteryTicket $ticket, Draw $draw): array
    {
        if (!$ticket->game->equals($draw->game)) {
            throw new GameMismatchException('Ticket and draw must use identical game rules.');
        }
        $results = [];
        foreach ($ticket->bets as $index => $bet) {
            $matches = [];
            foreach ($bet as $pool => $numbers) {
                $available = array_count_values($draw->numbers[$pool]);
                $matches[$pool] = [];
                foreach ($numbers as $number) {
                    if (($available[$number] ?? 0) > 0) {
                        $matches[$pool][] = $number;
                        --$available[$number];
                    }
                }
            }
            $results[] = new MatchResult($index, $draw->id, $matches);
        }

        return $results;
    }
}
