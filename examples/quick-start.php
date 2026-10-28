<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawImporter;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\Source\InMemoryDrawSource;
use Rupcom\LotteryToolkit\Statistics\StatisticsAnalyzer;
use Rupcom\LotteryToolkit\Ticket\LotteryTicket;
use Rupcom\LotteryToolkit\Ticket\NumberGenerator;
use Rupcom\LotteryToolkit\Ticket\TicketChecker;

$game = GameRules::lotto();
$importer = new DrawImporter();
$analyzer = new StatisticsAnalyzer();
$checker = new TicketChecker();
$generator = new NumberGenerator();

$source = new InMemoryDrawSource([
    new DrawRecord('demo-1', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]]),
    new DrawRecord('demo-2', '2026-01-03', ['main' => [2, 3, 4, 5, 6, 7]]),
]);
$draws = $importer->import($game, $source);
$report = $analyzer->analyze($draws->last(10));
$ticket = new LotteryTicket($game, [['main' => [1, 2, 3, 4, 5, 6]]]);
$latest = $draws->latest() ?? throw new LogicException('The example requires draws.');
$matches = $checker->check($ticket, $latest);
$randomTicket = $generator->generate($game, bets: 2);
echo json_encode(['statistics' => $report, 'matches' => $matches, 'randomBets' => $randomTicket->bets], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
