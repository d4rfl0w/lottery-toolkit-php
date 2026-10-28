# API reference

All core names below are relative to `Rupcom\LotteryToolkit`. Import the named
classes or use fully qualified names. Examples in this reference are short API
fragments; the [README](../README.md) and [examples guide](examples.md) contain
complete executable programs.

PHP scalar arguments are typed, and all package PHP files use strict types. PHP
callers should also use `declare(strict_types=1)`. Runtime validation of nested
arrays is strict regardless of the caller's scalar coercion mode.

## Shared shapes and exceptions

- **Numbers:** `array<string,list<int>>`, one entry for each configured pool.
- **Bets:** a non-empty list of Numbers maps; indices are zero-based.
- **Import record:** exactly `{id: string, date: string, numbers: Numbers}`.
- **Histogram:** keys identify numbers, sums, distributions or pairs; values count
  occurrences or draws as documented.

All expected domain exceptions live in `Exception\` and extend
`LotteryException`, which extends PHP `RuntimeException`:

| Class | Purpose and example | Limits |
| --- | --- | --- |
| `LotteryException` | Catch the library's expected domain/import failures: `catch (LotteryException $e)` | Does not include PHP TypeError, memory exhaustion or random engine errors |
| `ValidationException` | Invalid rules, number lists, limits, unknown games/pools | Inspect message for diagnostics; messages are not stable machine codes |
| `GameMismatchException` | Draw, collection and ticket rules differ | Same game ID alone is insufficient |
| `ImportException` | Malformed source data, invalid dates or provider I/O failure | Importer wraps validation failures with record context and previous cause |

Exception constructors follow `RuntimeException::__construct(string $message = '',
int $code = 0, ?Throwable $previous = null)`. Use `getMessage(): string` and
`getPrevious(): ?Throwable` for diagnostics. The base class remains extensible so
providers can define domain-specific exceptions. Leaf exceptions are final.

```php
try {
    $draws = (new DrawImporter())->fromJson(GameRules::lotto(), '{invalid');
} catch (\Rupcom\LotteryToolkit\Exception\ImportException $error) {
    // Log $error internally; choose a safe application response.
}
```

## NumberPool

An immutable definition of one unordered selection pool. Public readonly
properties: `name: string`, `count: positive-int`, `min: int`, `max: int`,
`allowDuplicates: bool`.

| Method | Parameters | Result | Throws |
| --- | --- | --- | --- |
| `__construct` | `string $name, int $count, int $min, int $max, bool $allowDuplicates = false` | NumberPool | ValidationException for invalid identifiers, bounds or impossible counts |
| `validate` | `array<array-key,mixed> $numbers` | Sorted `list<int>` copy | ValidationException for wrong length, non-list input, wrong types, duplicates or values outside bounds |

Names match `[a-z][a-z0-9_]*`; `id` and `date` are reserved. Picks are 1–100;
`1 <= min <= max <= 1,000,000`; at most 10,000 different possible values. Without
repetition, count cannot exceed the number of possible values. With repetition,
count can exceed that range. These are selection rules, not a sampled set of numbers.

```php
$pool = new NumberPool('main', count: 3, min: 1, max: 10);
$numbers = $pool->validate([9, 1, 4]); // [1, 4, 9]
```

## GameRules

An immutable game ID and named pools. Public readonly properties: `id: string`,
`pools: non-empty-array<string,NumberPool>`. Pool keys are canonicalized by name.

| Method | Parameters | Result | Throws |
| --- | --- | --- | --- |
| `__construct` | `string $id, array<array-key,NumberPool> $pools` | GameRules | ValidationException for invalid ID, empty/non-list input or duplicate names |
| `lotto` (static) | None | `GameRules`, 6/49 main pool | None |
| `miniLotto` (static) | None | `GameRules`, 5/42 main pool | None |
| `eurojackpot` (static) | None | `GameRules`, 5/50 main and 2/12 euro | None |
| `pool` | `string $name` | NumberPool | ValidationException for unknown pool |
| `validate` | `array<array-key,mixed> $numbers` | Canonical Numbers copy | ValidationException for missing/extra pools or invalid pool selections |
| `equals` | `GameRules $other` | bool | None |

Game IDs match the lowercase identifier pattern. Equality compares the complete
rule definition, independent of input pool order. All pools are independent;
overlapping values in different pools are valid. Historical rules require an
appropriate custom definition; no effective-date switching is implicit.

```php
$game = new GameRules('demo', [new NumberPool('main', 3, 1, 10)]);
$numbers = $game->validate(['main' => [8, 2, 5]]);
$same = $game->equals(new GameRules('demo', [new NumberPool('main', 3, 1, 10)])); // true
```

## GameRegistry

Immutable lookup containing the three presets plus your overrides.

| Method | Parameters | Result | Throws |
| --- | --- | --- | --- |
| `__construct` | `iterable<GameRules> $games = []` | GameRegistry | No additional domain validation; supplied games are already validated |
| `get` | `string $id` | GameRules | ValidationException for unknown ID |
| `all` | None | `array<string,GameRules>` copy | None |

Later definitions with the same ID intentionally override earlier ones, including
presets. This differs from draw IDs, where duplicates always fail.

```php
$registry = new GameRegistry([new GameRules('demo', [new NumberPool('main', 2, 1, 9)])]);
$game = $registry->get('demo');
$presetsAndCustom = $registry->all();
```

## Draw

A validated immutable outcome. Public readonly properties: `game: GameRules`,
`id: string`, `date: DateTimeImmutable`, `numbers: Numbers`.

`__construct(GameRules $game, string $id, DateTimeImmutable $date, array $numbers)`
validates the non-blank ID and selections, throwing `ValidationException` on invalid
domain data. Returns a Draw. The date is converted to UTC without changing its
instant or fractional precision. Direct construction does not parse date strings.
IDs distinguish draws at the same instant. IDs are opaque strings, not necessarily
numeric or a date, and do not imply a particular source.

```php
$draw = new Draw(GameRules::lotto(), 'demo-1', new DateTimeImmutable('2026-01-01T20:00:00Z'),
    ['main' => [1, 2, 3, 4, 5, 6]]);
```

## DrawCollection

Immutable, eager, single-game history implementing `Countable` and
`IteratorAggregate<int,Draw>`. Public readonly `game: GameRules` remains available
for empty histories.

| Method | Parameters | Result | Throws |
| --- | --- | --- | --- |
| `__construct` | `GameRules $game, iterable<Draw> $draws = []` | DrawCollection | GameMismatchException for different rules; ValidationException for duplicate IDs |
| `count` | None | int, usable as `count($draws)` | None |
| `getIterator` | None | `Traversable<int,Draw>`, usable in foreach | None |
| `toArray` | None | `list<Draw>` copy | None |
| `latest` | None | `?Draw`, null for empty history | None |
| `between` | `?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null` | New DrawCollection | ValidationException if from > to |
| `last` | `int $count` | New DrawCollection | ValidationException if count < 1 |

Order is ascending instant, then ascending bytewise ID. Date bounds are inclusive
instants with their supplied timezone. Null bounds are unbounded. `last(100)` on
three draws returns all three. Filtering does not mutate the original and does
not automatically fill missing dates. There is no implicit database query.

```php
$history = new DrawCollection($draw->game, [$draw]);
$selection = $history->between(from: new DateTimeImmutable('2026-01-01T00:00:00Z'))->last(10);
foreach ($selection as $item) {
    echo $item->id;
}
```

## DrawImporter

Stateless, strict import service with a no-argument constructor.

| Method | Parameters | Result | Throws |
| --- | --- | --- | --- |
| `fromArray` | `GameRules $game, iterable<mixed> $rows` | DrawCollection | ImportException on malformed record, invalid values/date, conflicting game data or duplicate IDs |
| `fromJson` | `GameRules $game, string $json` | DrawCollection | ImportException on invalid JSON, non-list root or invalid record |
| `fromCsv` | `GameRules $game, string $csv, string $delimiter = ','` | DrawCollection | ImportException on malformed quoting/header/cells, invalid separator or invalid record |

Inputs are **contents**, not file paths. Import is all-or-nothing. Empty PHP/JSON
lists and header-only CSV are accepted. The importer does not fetch external data
and does not guess schemas, dates, pool names or missing IDs. An upstream iterable
that throws its own transport exception propagates it unless it is a
LotteryException, in which case record context is added.

Strict supported formats, quoting and timezone rules are specified in
[getting started](getting-started.md#import-contracts).

```php
$importer = new DrawImporter();
$csvDraws = $importer->fromCsv(GameRules::lotto(), "id,date,main\na,2026-01-01,1 2 3 4 5 6\n");
$jsonDraws = $importer->fromJson(GameRules::lotto(), '[]');
$arrayDraws = $importer->fromArray(GameRules::lotto(), []);
```

## DrawProviderInterface

`getDraws(GameRules $game): DrawCollection` supplies an already validated history
for exactly those rules. Implementations own I/O, caching, pagination, credentials,
source mappings and supported-game decisions. Expected source failures should
throw `LotteryException` (usually `ImportException`); an empty history is a valid
result, not a fallback for unavailable data. No sorting burden is placed on the
caller: collection construction canonicalizes order.

Example: `$draws = $provider->getDraws(GameRules::lotto());`.
See the [complete provider implementation](custom-providers.md).

## StatisticsAnalyzer

Stateless service with no constructor arguments.
`analyze(DrawCollection $draws, string $pool = 'main'): StatisticsReport`
calculates one pool's descriptive statistics. Throws `ValidationException` if the
pool does not exist. Empty collections are supported. Filter the collection before
calling this method. Analysis materializes histograms and costs O(D*K² + R);
there is no streaming, cross-pool pair analysis or prediction.

```php
$report = (new StatisticsAnalyzer())->analyze($draws->last(100), pool: 'main');
```

## StatisticsReport

Readonly DTO returned by `StatisticsAnalyzer`; its constructor is internal and
is not a supported construction API. Public fields and their shapes are listed in
[statistics](statistics.md#definitions). All fields are readonly snapshots.

| Method | Parameters | Result | Throws |
| --- | --- | --- | --- |
| `ranked` | `FrequencyOrder $order = FrequencyOrder::MostFrequent, int $limit = 10` | Ordered `array<int,int>` number → occurrences | ValidationException if limit < 1 |
| `mostFrequent` | `int $limit = 10` | Same map, descending counts | ValidationException if limit < 1 |
| `leastFrequent` | `int $limit = 10` | Same map, ascending counts | ValidationException if limit < 1 |
| `mostFrequentPairs` | `int $limit = 10` | Ordered `array<string,int>` pair → draw count | ValidationException if limit < 1 |
| `jsonSerialize` | None | `array<string,mixed>` with identity, drawCount and histogram objects | None |

Rankings preserve keys, include unseen numbers and return fewer entries when fewer
exist. Frequency ties sort by number ascending; pair ties compare numeric members.
Recency is measured in selected draws: null for unseen, zero for latest. Repeated
pool values increment frequency for each occurrence; a pair counts at most once per
draw. JSON field names match properties and maps are always objects, even when empty.

```php
$most = $report->mostFrequent(6);
$least = $report->ranked(FrequencyOrder::LeastFrequent, limit: 6);
$pairs = $report->mostFrequentPairs(3);
$json = json_encode($report, JSON_THROW_ON_ERROR);
```

## FrequencyOrder

Unit enum with `MostFrequent` and `LeastFrequent` cases. Used by
`StatisticsReport::ranked()`. `FrequencyOrder::cases(): array` is PHP's built-in
enum case enumeration. There are no arbitrary string modes and no backed value.

Example: `$report->ranked(FrequencyOrder::LeastFrequent, 3)`.

## LotteryTicket

Immutable validated ticket containing multiple independent bets. Public readonly
properties: `game: GameRules`, `bets: non-empty-list<Numbers>`.

`__construct(GameRules $game, array<array-key,mixed> $bets)` returns a ticket and
throws `ValidationException` for an empty/non-list input, a non-map bet, wrong pool
names, number types/counts, range violations or forbidden repetition. Each bet is
canonicalized by game validation. Duplicate whole bets are allowed; system bets
and differing bet/draw pool sizes are outside the current model.

```php
$ticket = new LotteryTicket(GameRules::miniLotto(), [
    ['main' => [1, 2, 3, 4, 5]], ['main' => [6, 7, 8, 9, 10]],
]);
```

## TicketChecker

Stateless service with no constructor arguments.
`check(LotteryTicket $ticket, Draw $draw): non-empty-list<MatchResult>` compares each
bet and each pool. Throws `GameMismatchException` unless complete definitions are
equal. It does not verify draw authenticity, purchase timing, payout eligibility
or monetary value. Results preserve the input bet order and have zero-based indices.
Repeated pools use multiset intersection: each draw occurrence is consumed once.

```php
$matches = (new TicketChecker())->check($ticket, $compatibleDraw);
$mainHitsInFirstBet = $matches[0]->count('main');
```

## MatchResult

Readonly DTO for one bet. Public fields: `betIndex: int`, `drawId: string`,
`matchedNumbers: array<string,list<int>>`.

| Method | Parameters | Result | Throws |
| --- | --- | --- | --- |
| `__construct` | `int $betIndex, string $drawId, array<string,list<int>> $matchedNumbers` | MatchResult | ValidationException for negative index or blank ID |
| `count` | `string $pool = 'main'` | int, number of matches in that pool | ValidationException for unknown pool |
| `total` | None | int, sum of counts across pools | None |
| `jsonSerialize` | None | `{betIndex:int, drawId:string, matchedNumbers:Numbers, counts:array<string,int>, total:int}` | None |

Prefer instances produced by `TicketChecker`; manually constructing this report
cannot prove an actual match because it has no source draw or ticket. The total
is informational: it is not a prize tier and does not rank Eurojackpot outcomes.

```php
$result = $matches[0];
$main = $result->count();
$allPools = $result->total();
echo json_encode($result, JSON_THROW_ON_ERROR);
```

## NumberGenerator

Produces validated tickets with independent random selections per pool.

| Method | Parameters | Result | Throws |
| --- | --- | --- | --- |
| `__construct` | `?Random\Randomizer $randomizer = null` | NumberGenerator | Native engine errors, if any |
| `generate` | `GameRules $game, int $bets = 1` | LotteryTicket | ValidationException for bets outside 1–10,000; native Random errors on engine failure |

The default engine is `Random\Engine\Secure`. Unique pools sample uniformly
without replacement. Repeating pools draw each pick independently with replacement.
Output is sorted and revalidated through LotteryTicket. A custom engine can be
predictable and should be used knowingly. Different bets may be identical.

```php
$ticket = (new NumberGenerator())->generate(GameRules::lotto(), bets: 5);
$testGenerator = new NumberGenerator(new \Random\Randomizer(new \Random\Engine\Mt19937(42)));
```

## Bridge\Symfony\LotteryToolkitBundle

Optional `AbstractBundle` subclass. Register its FQCN in `config/bundles.php`.
The extension alias is `lottery_toolkit`. Its supported options, service IDs and
aliases are described in the [Symfony guide](symfony-integration.md).

`configure(DefinitionConfigurator $definition): void` declares the Symfony config
tree. `loadExtension(array $config, ContainerConfigurator $container,
ContainerBuilder $builder): void` registers services and validates configured game
rules. Symfony calls both at compile time; applications should not call them
directly. Invalid configuration yields Symfony configuration exceptions or a
library ValidationException. Classes require compatible Symfony packages to load.

Example registration:
`LotteryToolkitBundle::class => ['all' => true]` in the bundles map.

## Bridge\Symfony\DomainExceptionSubscriber

Optional event subscriber enabled by `lottery_toolkit.map_exceptions: true`.
`getSubscribedEvents(): array<string,string>` (static) maps `kernel.exception` to
`onException`. `onException(ExceptionEvent $event): void` sets a generic JSON 422
response for ValidationException/GameMismatchException and otherwise leaves the
event unchanged. It does not throw new domain exceptions or expose original
messages. Do not register it twice. The subscriber is global for these exception
classes when enabled, so opt out if the application has its own translation policy.

Example configuration: `lottery_toolkit: { map_exceptions: true }`.
