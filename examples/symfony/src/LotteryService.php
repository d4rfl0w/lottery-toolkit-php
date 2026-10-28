<?php

declare(strict_types=1);

namespace LotteryToolkitExample;

use Rupcom\LotteryToolkit\{DrawProviderInterface, GameRules, LotteryTicket, MatchResult, StatisticsAnalyzer, StatisticsReport, TicketChecker};
use Rupcom\LotteryToolkit\Exception\ValidationException;

final readonly class LotteryService
{
    public function __construct(
        private DrawProviderInterface $provider,
        private GameRules $game,
        private StatisticsAnalyzer $analyzer,
        private TicketChecker $checker,
    ) {
    }

    public function statistics(): StatisticsReport
    {
        return $this->analyzer->analyze($this->provider->getDraws($this->game));
    }

    /**
     * @param array<array-key, mixed> $bets
     * @return non-empty-list<MatchResult>
     */
    public function check(array $bets): array
    {
        $ticket = new LotteryTicket($this->game, $bets);
        $draw = $this->provider->getDraws($this->game)->latest()
            ?? throw new ValidationException('No draw is available for this game.');

        return $this->checker->check($ticket, $draw);
    }
}
