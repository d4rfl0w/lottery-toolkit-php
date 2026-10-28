# Descriptive statistics

`StatisticsAnalyzer::analyze(DrawCollection $draws, string $pool = 'main')`
returns an immutable `StatisticsReport`. It always analyzes one explicitly named
pool. Main and Euro numbers never share a histogram, pair table or match count.

## Definitions

| Field | PHP shape | Meaning |
| --- | --- | --- |
| `gameId`, `pool` | `string` | Identity of the requested analysis |
| `drawCount` | `int` | Number of selected draws |
| `frequency` | `array<int,int>` | Number → occurrence count, including zeroes |
| `drawsSinceLastOccurrence` | `array<int,int\|null>` | Number → draws after last appearance |
| `oddEvenDistribution` | `array<string,int>` | `odd:even` → number of draws |
| `sumDistribution` | `array<int,int>` | Sum of pool numbers → number of draws |
| `pairs` | `array<string,int>` | `smaller:larger` → number of draws containing pair |

Recency is relative to the **selected** history: `0` means latest draw; `1` means
one selected draw has happened since the latest appearance. `null` means unseen,
not overdue. It is not elapsed days. The sample may omit actual intervening draws;
completeness is the provider's responsibility.

Frequency counts every occurrence. For a custom repeating pool containing
`[1,1,1,2]`, frequencies are `1 => 3` and `2 => 1`, the sum is 5 and odd/even is
`3:1`. Pairs `1:1` and `1:2` each receive one increment for that draw. The pair
`2:2` is absent because it needs at least two copies. Pools are unordered; these
are co-occurrences, not ordered positional pairs.

`mostFrequent($limit)` and `leastFrequent($limit)` preserve number keys, include
unseen numbers and break count ties by ascending number. `ranked(FrequencyOrder,
$limit)` exposes the same ordering explicitly. `mostFrequentPairs($limit)` breaks
ties by the two numeric members, not lexicographic string order. Limits must be
positive; a limit larger than the result size returns all results. Raw `pairs`
has deterministic key order but is not ranked. Use `mostFrequentPairs()` for ranking.

## Filtering

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawImporter;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\Source\InMemoryDrawSource;
use Rupcom\LotteryToolkit\Statistics\StatisticsAnalyzer;

$importer = new DrawImporter();

$history = $importer->import(GameRules::lotto(), new InMemoryDrawSource([
    new DrawRecord('a', '2026-01-01', ['main' => [1,2,3,4,5,6]]),
    new DrawRecord('b', '2026-01-03', ['main' => [2,3,4,5,6,7]]),
]));
$selected = $history->between(from: new DateTimeImmutable('2026-01-02T00:00:00Z'))->last(1);
$report = (new StatisticsAnalyzer())->analyze($selected);
if ($report->drawsSinceLastOccurrence[1] !== null || $report->frequency[7] !== 1) {
    throw new RuntimeException('Filtering must precede analysis.');
}
```

Bounds are inclusive instants. `between($from, $to)->last(10)` selects the latest
ten draws inside that interval. `last(10)->between($from, $to)` can produce a
different result. For a whole local calendar day, create start/end bounds in the
intended timezone (including DST); UTC midnight is not automatically local midnight.
An end bound at midnight excludes later times on that date.

Empty history yields zero frequencies, null recencies, and empty distribution and
pair maps. There is no division by zero and no fabricated observation. JSON
serialization of report maps always yields objects, including `{}` for empty maps.

## Cost and limits

For D draws, K picks per pool and R possible numbers, analysis costs
`O(R + D*K²)` time. The result requires `O(R + P + S + K)` memory, where P is the
number of observed pairs and S the number of observed sums. History storage is
`O(D*K)` per pool, and collection construction sorts in `O(D log D)` time. Filters
return independent collections and currently revalidate/sort their contents.
Imports and analysis are in-memory operations; large archives should be bounded
by the application. Provider pagination does not make this analyzer streaming.

Pools allow at most 100 picks, 10,000 possible values and a maximum value of
1,000,000. These explicit bounds constrain enumeration and pair work. A generation
call allows at most 10,000 bets. They are part of the v0.1 validation contract.

Historical rankings do not change the odds of an independent future draw. This
library deliberately has no prediction, confidence score, betting strategy or
monetary return calculation.
