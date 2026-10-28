# Getting started

## Install and run

The core requires PHP 8.3 or later and no framework. The first release is prepared
but has not been published. Use the local Composer path installation in the
[README](../README.md#installation) now; after publication use:

```sh
composer require rupcom/lottery-toolkit:^0.1
```

For this checkout, `composer install` installs development tools and Symfony so
you can run `php examples/quick-start.php` and `composer check`.
A production consumer's `require` does not install this library's `require-dev`.

## Model the game and its history

Each game has one or more named unordered pools. Draws and bets use the same pool
counts. This release does not model games where a bet has a different size from a
draw, ordered digit games, bonus balls drawn from a depleted main pool, or system
bet expansion. Such games need a separate contract instead of approximating them
with these presets.

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\Draw\Draw;
use Rupcom\LotteryToolkit\Draw\DrawCollection;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Game\NumberPool;
use Rupcom\LotteryToolkit\Statistics\StatisticsAnalyzer;

$game = new GameRules('community_draw', [new NumberPool('main', 2, 10, 15)]);
$draw = new Draw($game, 'synthetic-1', new DateTimeImmutable('2026-01-01T12:00:00Z'),
    ['main' => [15, 10]]);
$history = new DrawCollection($game, [$draw]);
$report = (new StatisticsAnalyzer())->analyze($history);
if ($report->frequency[10] !== 1 || $draw->numbers['main'] !== [10, 15]) {
    throw new RuntimeException('Unexpected canonical result.');
}
```

Numbers are sorted when validated; original arrays cannot mutate the objects.
`DrawCollection` sorts by instant ascending, then by bytewise draw ID ascending.
For simultaneous draws, the lexically largest ID is treated as latest. Providers
should choose stable IDs, and consumers should understand this deterministic tie
rule when selecting the last N draws.

## Import contracts

All imports are atomic: the first malformed record aborts the import, and no partial
collection is returned. Duplicate IDs are errors, including identical repeated
records. Preprocess intentional deduplication in your provider.

| Input | Contract |
| --- | --- |
| PHP | `InMemoryDrawSource` containing `iterable<DrawRecord>` with typed `id`, `date`, `numbers` fields |
| JSON | A top-level array of objects with exactly `id`, `date`, `numbers`; no envelope and no BOM |
| CSV | Header contains exactly `id`, `date` and all game pool names, in any order |

`id` must be a non-blank string. `numbers` contains exactly the game's named pools.
PHP/JSON numbers must be integers, not strings, floats, booleans or null. Every pool
is a list, not a sparse associative array. Extra JSON record fields are rejected; map
provider-specific metadata before import.

CSV uses one space between integers in each pool cell. Leading/trailing cell
whitespace is trimmed for numbers only. Empty lines are skipped. Headers are case
sensitive and unique. A UTF-8 BOM at the beginning is accepted. LF/CRLF, quoted
commas, multiline fields and doubled quotes are supported; backslash escaping is
not. `new CsvDrawSource($contents, delimiter: ';')` supports an alternate single-byte
separator, except space, quote, NUL or newline. Malformed quoting, missing columns,
extra columns and oversized integers fail explicitly. A header-only CSV is an
empty collection; a completely empty CSV is an error.

Example Eurojackpot CSV:

```csv
id,date,main,euro
synthetic-1,2026-01-01,1 2 3 4 5,10 12
```

Dates are strict `YYYY-MM-DD` (UTC midnight) or RFC3339 with seconds and explicit
offset, such as `2026-01-01T21:00:00+01:00` or `2026-01-01T20:00:00Z`.
Fractional seconds, timezone-less timestamps, relative expressions, calendar
overflow and offsets outside ±14:00 are rejected. Direct `Draw` construction
accepts any `DateTimeImmutable`, retaining subsecond precision and normalizing its
zone to UTC. Importers deliberately support the narrower portable text format.

## Load a file explicitly

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\Exception\ImportException;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawImporter;
use Rupcom\LotteryToolkit\Import\Source\CsvDrawSource;

$csv = @file_get_contents('examples/symfony/data/draws.csv');
if ($csv === false) {
    throw new ImportException('Cannot read the configured fixture.');
}
$importer = new DrawImporter();
$draws = $importer->import(GameRules::lotto(), new CsvDrawSource($csv));
if (count($draws) !== 3) {
    throw new RuntimeException('Expected three synthetic records.');
}
```

This checkout-specific example uses the bundled synthetic file. In an application,
use an application-owned path; never pass an unchecked user path or URL to the
filesystem. The importer itself performs no network or file access (CSV parsing
uses an internal temporary stream).

## Errors

Catch `Rupcom\LotteryToolkit\Exception\LotteryException` for all expected library
failures. Narrow to `ValidationException`, `GameMismatchException` or
`ImportException` if you need different responses. Import errors retain their
underlying exception and report the one-based record position. Native `TypeError`
is still possible for incorrect PHP argument types. Random engine failures retain
PHP's native random exceptions. Avoid catching all errors and returning an empty
history: missing history and an unavailable provider are different outcomes.

See [the API reference](api-reference.md), [statistics](statistics.md) and
[troubleshooting](troubleshooting.md).
