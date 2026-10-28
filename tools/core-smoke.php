<?php

declare(strict_types=1);

require $argv[1] ?? dirname(__DIR__).'/vendor/autoload.php';

use Rupcom\LotteryToolkit\{DrawImporter, GameRules, LotteryTicket, NumberGenerator, StatisticsAnalyzer, TicketChecker};

if (class_exists(\Symfony\Component\HttpKernel\Kernel::class)) {
    throw new RuntimeException('The standalone smoke test must run without Symfony installed.');
}
$game = GameRules::lotto();
$draws = (new DrawImporter())->fromCsv($game, "id,date,main\na,2026-01-01,1 2 3 4 5 6\n");
$report = (new StatisticsAnalyzer())->analyze($draws);
$draw = $draws->latest() ?? throw new RuntimeException('Missing draw.');
$matches = (new TicketChecker())->check(new LotteryTicket($game, [['main' => [1, 2, 3, 4, 5, 6]]]), $draw);
if ($report->frequency[1] !== 1 || $matches[0]->count() !== 6 || count((new NumberGenerator())->generate($game)->bets) !== 1) {
    throw new RuntimeException('Core consumer smoke test failed.');
}
echo "Core consumer works without Symfony.\n";
