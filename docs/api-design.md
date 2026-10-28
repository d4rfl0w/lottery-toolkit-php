# API design

The examples below describe the modular API of the unreleased library.
All names, schemas and semantics are intentional public contracts.

```php
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
$draws = $importer->import($game, new InMemoryDrawSource([
    new DrawRecord('demo-1', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]]),
    new DrawRecord('demo-2', '2026-01-03', ['main' => [2, 3, 4, 5, 6, 7]]),
]));
$report = (new StatisticsAnalyzer())->analyze($draws->last(10));
$top = $report->mostFrequent(5);
$ticket = new LotteryTicket($game, [['main' => [1, 2, 3, 4, 5, 6]]]);
$matches = (new TicketChecker())->check($ticket, $draws->latest());
$randomTicket = (new NumberGenerator())->generate($game, bets: 2);
```

Rules and numbers are immutable and validated at construction. Each bet and draw
contains exactly the named pools defined by the game. Numbers are integers, sorted
canonically, and unique unless a pool explicitly permits repetition. Games compare
by their complete definitions, not only their IDs. Providers return a DrawCollection
for one game; consumers can depend on DrawProviderInterface. Empty histories are
valid, but latest() returns null. last(N) requires N >= 1. Date bounds are inclusive
instants; date-only imports mean UTC midnight. Ties use draw ID in ascending order.

Statistics are independent per pool. Frequency counts occurrences, pairs count at
most once per draw, recency counts draws since the latest occurrence (0 means the
latest draw; null means never seen in the selected history). Rankings include unseen
numbers and break ties by number. Matching repeated numbers uses multiset semantics.
There are no predictions, prize tables, network requests or persistence in the core.

Symfony registers the analyzer, importer, checker, generator, a registry of
built-in and configured games, and an explicitly configured provider interface alias.
The example kernel exercises the same application service over HTTP and Console.
