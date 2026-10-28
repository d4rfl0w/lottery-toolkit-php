<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Rupcom\LotteryToolkit\{DrawImporter, GameRules, LotteryTicket, NumberGenerator, StatisticsAnalyzer, TicketChecker};

$game = GameRules::lotto();
$draws = (new DrawImporter())->fromArray($game, [
    ['id' => 'demo-1', 'date' => '2026-01-01', 'numbers' => ['main' => [1, 2, 3, 4, 5, 6]]],
    ['id' => 'demo-2', 'date' => '2026-01-03', 'numbers' => ['main' => [2, 3, 4, 5, 6, 7]]],
]);
$report = (new StatisticsAnalyzer())->analyze($draws->last(10));
$ticket = new LotteryTicket($game, [['main' => [1, 2, 3, 4, 5, 6]]]);
$latest = $draws->latest() ?? throw new LogicException('The example requires draws.');
$matches = (new TicketChecker())->check($ticket, $latest);
$randomTicket = (new NumberGenerator())->generate($game, bets: 2);
echo json_encode(['statistics' => $report, 'matches' => $matches, 'randomBets' => $randomTicket->bets], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
