# Lottery Toolkit for PHP

A small, framework-independent PHP library for lottery game rules, validated draw
imports, descriptive statistics, ticket matching and secure random combinations.
An optional Symfony bundle provides configuration, autowiring and provider aliases.

**Package:** `rupcom/lottery-toolkit` · **Namespace:** `Rupcom\LotteryToolkit` ·
**License:** MIT · **First release target:** `v0.1.0` (not published yet).

[Getting started](docs/getting-started.md) · [API reference](docs/api-reference.md) ·
[Symfony guide](docs/symfony-integration.md) · [Polski](README.pl.md)

## Features

- Lotto, Mini Lotto, Eurojackpot and configurable games with independent number pools.
- Strict CSV, JSON and PHP array imports; a provider interface for your own data sources.
- Frequency rankings, recency, pairs, odd/even and sum distributions.
- Inclusive date filtering and analysis of the latest N draws.
- Immutable, validated draws and multiple-bet tickets; structured match reports.
- Secure randomness by default, with injectable engines for reproducible tests.
- An executable Symfony application with REST endpoints and a Console command.

Statistics describe the supplied history. They do not predict winning numbers or
improve the probability of a randomly chosen combination. Matching reports count
hits; they do not determine prize entitlement or monetary payouts. This project is
not affiliated with a lottery operator. All included draw data is synthetic.

## Requirements

PHP **8.3+**. The core has **no Composer runtime dependencies** other than PHP.
Symfony is optional. Development checks require Composer 2 and the extensions
required by PHPUnit (including DOM, XML, XMLWriter and mbstring).

See the [validation record](docs/validation.md) for the exact tested PHP/Symfony
versions. Compatibility constraints are not a claim that every historical patch
release has been tested.

## Installation

After the first release is published to Packagist:

```sh
composer require rupcom/lottery-toolkit:^0.1
```

Until then, install from a local checkout in your consuming project:

```sh
composer config repositories.lottery-toolkit path ../lottery-toolkit-php
composer require 'rupcom/lottery-toolkit:@dev'
```

To work on this repository and run its examples:

```sh
composer install
php examples/quick-start.php
```

## Quick Start

Save this as a PHP file (add `<?php`) in the directory containing `vendor/`.
It is also tested directly from this README.

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\DrawImporter;
use Rupcom\LotteryToolkit\GameRules;
use Rupcom\LotteryToolkit\StatisticsAnalyzer;

$draws = (new DrawImporter())->fromArray(GameRules::lotto(), [
    ['id' => 'demo-1', 'date' => '2026-01-01', 'numbers' => ['main' => [1, 2, 3, 4, 5, 6]]],
    ['id' => 'demo-2', 'date' => '2026-01-03', 'numbers' => ['main' => [2, 3, 4, 5, 6, 7]]],
]);
$report = (new StatisticsAnalyzer())->analyze($draws);
echo json_encode($report->mostFrequent(3), JSON_THROW_ON_ERROR); // {"2":2,"3":2,"4":2}
```

## Basic Usage

`GameRules` describes a game, `DrawCollection` owns its validated chronological
history, and `StatisticsAnalyzer` produces one immutable `StatisticsReport` per
pool. `LotteryTicket` validates bets on construction; `TicketChecker` compares them
against a compatible `Draw`. No database, application kernel or API key is needed.

Histories may be empty: `latest()` then returns `null`, frequency is zero and
recency is `null`. Check for an available draw before checking a ticket.

## Advanced Usage

Compose filters before analysis. Here the end bound is an exact inclusive instant.

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\{DrawImporter, GameRules, NumberPool, StatisticsAnalyzer};

$game = new GameRules('demo', [new NumberPool('main', count: 3, min: 1, max: 10)]);
$history = (new DrawImporter())->fromJson($game,
    '[{"id":"demo","date":"2026-01-03","numbers":{"main":[1,5,9]}}]');
$selected = $history->between(
    new DateTimeImmutable('2026-01-01T00:00:00Z'),
    new DateTimeImmutable('2026-01-31T23:59:59Z'),
)->last(20);
echo json_encode((new StatisticsAnalyzer())->analyze($selected), JSON_THROW_ON_ERROR);
```

Use composition to add caching or persistence. Implement `DrawProviderInterface`
for alternative storage. Core service classes are final; the provider interface
and injected `Randomizer` are the intended implementation replacement points.
See [architecture and compatibility](docs/architecture.md).

## Supported Lottery Games

| Factory | Game ID | Pools | Repetition |
| --- | --- | --- | --- |
| `GameRules::lotto()` | `lotto` | `main`: 6 from 1–49 | No |
| `GameRules::miniLotto()` | `mini_lotto` | `main`: 5 from 1–42 | No |
| `GameRules::eurojackpot()` | `eurojackpot` | `main`: 5 from 1–50; `euro`: 2 from 1–12 | No |
| `new GameRules(...)` | Your identifier | Named `NumberPool` objects | Configurable per pool |

Presets describe number selections, not draw schedules, ticket prices or payout
rules. Eurojackpot's preset uses the 2-of-12 Euro pool; use custom, versioned game
IDs when importing history governed by earlier rules. Names of pools and game IDs
are lowercase identifiers. Pool names `id` and `date` are reserved for CSV headers.

## Importing Draw Results

Every record has exactly `id` (non-blank string), `date` (date or timestamp) and
`numbers` (map of named integer lists). JSON/PHP numeric strings are rejected.
Date-only values mean UTC midnight; timestamps require seconds and a timezone.
Each history must have unique draw IDs and one complete set of game rules.

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\{DrawImporter, GameRules};

$importer = new DrawImporter();
$game = GameRules::lotto();
$csv = "id,date,main\ndemo-1,2026-01-01,1 2 3 4 5 6\n";
$draws = $importer->fromCsv($game, $csv);
$json = '[{"id":"demo-1","date":"2026-01-01","numbers":{"main":[1,2,3,4,5,6]}}]';
$sameDraws = $importer->fromJson($game, $json);
if ($draws != $sameDraws) {
    throw new RuntimeException('Imports must agree.');
}
```

`fromCsv()` and `fromJson()` accept **contents, not filenames**. Read a local file
explicitly, check for an I/O failure, and pass its contents to the importer.
See [format contracts and errors](docs/getting-started.md#import-contracts).

## Statistical Analysis

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\{DrawImporter, GameRules, StatisticsAnalyzer};

$draws = (new DrawImporter())->fromArray(GameRules::eurojackpot(), [
    ['id' => 'demo-ej', 'date' => '2026-01-01',
     'numbers' => ['main' => [1, 2, 3, 4, 5], 'euro' => [10, 12]]],
]);
$report = (new StatisticsAnalyzer())->analyze($draws, pool: 'euro');
echo json_encode([
    'most' => $report->mostFrequent(2),
    'least' => $report->leastFrequent(2),
    'pairs' => $report->mostFrequentPairs(5),
    'recency' => $report->drawsSinceLastOccurrence,
    'oddEven' => $report->oddEvenDistribution,
    'sums' => $report->sumDistribution,
], JSON_THROW_ON_ERROR);
```

Frequency counts occurrences. Recency is `0` for an appearance in the latest
selected draw, and `null` for an unseen number. Pair frequency counts draws
containing each unordered pair, at most once per draw. Ties sort by ascending
number, or by both pair members numerically. [Statistical semantics](docs/statistics.md)
explain empty histories, repeated numbers and complexity.

## Ticket Validation

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\{Draw, GameRules, LotteryTicket, TicketChecker};

$game = GameRules::lotto();
$ticket = new LotteryTicket($game, [
    ['main' => [1, 2, 3, 4, 5, 6]],
    ['main' => [7, 8, 9, 10, 11, 12]],
]);
$draw = new Draw($game, 'demo', new DateTimeImmutable('2026-01-01T00:00:00Z'),
    ['main' => [1, 2, 3, 7, 8, 9]]);
$matches = (new TicketChecker())->check($ticket, $draw);
echo $matches[0]->count(); // 3
// Each MatchResult contains betIndex, drawId and matchedNumbers by pool.
echo json_encode($matches, JSON_THROW_ON_ERROR);
```

Wrong counts, unknown pools, duplicates and out-of-range or non-integer values
throw `ValidationException`. Game definitions must match, including pool ranges
and repetition rules; otherwise checking throws `GameMismatchException`.
No system-bet expansion or prize calculation is included in v0.1.

## Number Generation

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\{GameRules, NumberGenerator};

$ticket = (new NumberGenerator())->generate(GameRules::eurojackpot(), bets: 3);
echo json_encode($ticket->bets, JSON_THROW_ON_ERROR);
```

The default is PHP's `Random\Engine\Secure`. Inject a `Random\Randomizer` for
repeatable tests. Duplicates within a pool are prohibited unless explicitly enabled
in its rules; different bets may coincidentally be identical.

## Custom Providers

Implement the single method `getDraws(GameRules $game): DrawCollection`. Providers
own I/O, authorization, pagination, caching and source-specific mappings. Return a
validated collection for exactly the requested rules and wrap expected data
failures in an appropriate `LotteryException`. Never silently mix game versions.

[The custom provider guide](docs/custom-providers.md) contains a complete runnable
implementation. The [Symfony CSV provider](examples/symfony/src/CsvDrawProvider.php)
shows constructor injection. No remote provider, scraper or third-party dataset is
bundled.

## Symfony Integration

The optional `Rupcom\LotteryToolkit\Bridge\Symfony\LotteryToolkitBundle` registers
core services and a `GameRegistry`, supports custom game definitions, aliases
`GameRules` to your default game, and aliases `DrawProviderInterface` to your
configured provider. Enable it explicitly in `config/bundles.php`.

```php
Rupcom\LotteryToolkit\Bridge\Symfony\LotteryToolkitBundle::class => ['all' => true],
```

```yaml
# config/packages/lottery_toolkit.yaml
lottery_toolkit:
    provider: App\Lottery\MyDrawProvider
    default_game: lotto
    map_exceptions: true
    games:
        office_draw:
            pools:
                main: { count: 3, min: 1, max: 20 }
```

Register your provider as an autowired service. Inject `StatisticsAnalyzer`,
`GameRegistry`, `GameRules` or `DrawProviderInterface` into consumers.
`map_exceptions` is opt-in: validation/game mismatch becomes a generic JSON 422;
import failures keep normal server error handling.

Run the complete example from this checkout:

```sh
php examples/symfony/bin/console lottery:analyse --bets='[{"main":[1,3,5,7,9,11]}]'
php -S 127.0.0.1:8080 -t examples/symfony/public examples/symfony/public/index.php
curl http://127.0.0.1:8080/statistics
curl -X POST http://127.0.0.1:8080/tickets/check \
  -H 'Content-Type: application/json' -d '{"bets":[{"main":[1,3,5,7,9,11]}]}'
```

The development server runs in the foreground; use another terminal for curl.
See [the complete Symfony guide](docs/symfony-integration.md) for setup, service
contracts, configuration reference and tested compatibility.

## Testing

```sh
composer install
composer check
```

This runs Composer validation, PHPUnit, PHPStan at **level max** and PHP-CS-Fixer
with PSR-12. GitHub Actions tests PHP 8.3/8.4 with Symfony 6.4/7.x, plus a separate
core-only consumer install. Tests cover the compiled container, HTTP requests,
Console execution and the runnable examples in these guides. No real credentials
or production databases are used.

## Contributing

Read [CONTRIBUTING.md](CONTRIBUTING.md), [the code of conduct](CODE_OF_CONDUCT.md)
and the [three-month roadmap](docs/OPEN_SOURCE_ROADMAP.md). Good first contributions
include importer edge cases, provider contract tests and documentation examples.
Public API changes require documentation, tests and an explicit compatibility note.

## Security

See [SECURITY.md](SECURITY.md) for private reporting and maintenance scope. Keep
credentials in the consuming application. Impose request and upload size limits at
your application boundary; v0.1 intentionally uses in-memory collections.

## License

The original library, examples and documentation are licensed under the
[MIT License](LICENSE). Dependencies retain their own licenses; see
[THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md). No Lotto Assistant source code,
private data or provider database is included.
