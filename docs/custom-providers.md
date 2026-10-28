# Custom providers

`DrawProviderInterface` has one method:

```php
public function getDraws(GameRules $game): DrawCollection;
```

Return a validated collection for **exactly** the supplied rules, not just a matching
game ID. Empty collections are valid. The collection enforces unique IDs and a
chronological order. Do not silently discard malformed rows, change their numbers,
or treat a source outage as an empty result.

## A complete in-memory provider

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\Draw\DrawCollection;
use Rupcom\LotteryToolkit\Draw\DrawProviderInterface;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawImporter;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\Source\InMemoryDrawSource;
use Rupcom\LotteryToolkit\Statistics\StatisticsAnalyzer;

$provider = new class(new DrawImporter()) implements DrawProviderInterface {
    public function __construct(private readonly DrawImporter $importer) {}

    public function getDraws(GameRules $game): DrawCollection
    {
        if (!$game->equals(GameRules::lotto())) {
            return new DrawCollection($game);
        }
        return $this->importer->import($game, new InMemoryDrawSource([
            new DrawRecord('synthetic', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]]),
        ]));
    }
};
$report = (new StatisticsAnalyzer())->analyze($provider->getDraws(GameRules::lotto()));
echo $report->drawCount; // 1
```

This fixture provider intentionally returns no history for other games. Real
providers may instead throw `ValidationException` for unsupported rules; document
that choice. See the [executable CSV provider](../examples/symfony/src/CsvDrawProvider.php)
for file I/O and [Symfony configuration](symfony-integration.md) for registration.

## Remote sources and persistence

Keep HTTP clients, database repositories and credentials in your provider or a
separate adapter package. Map external fields explicitly to `DrawRecord` objects in a `DrawSourceInterface`
implementation, or construct validated draws directly.
Use stable provider IDs; fetch every page before returning a complete selected
history. Decide whether upstream duplicate records are retries or conflicting
results, then resolve or reject them before constructing the collection.

Wrap expected transport/format failures in `ImportException`, retaining the cause.
Use finite connect/read timeouts and bounded retries that respect the source's
rate limits. Cache by the complete game definition and query scope, not just ID.
Keep source timestamps and rule versions correct. Check licensing and permission
before fetching or redistributing a dataset. No data source authorization is
provided by installing this package.

For a database adapter, hydrate `Draw` objects and build `DrawCollection` directly;
Doctrine is not required by this package. For a provider decorator, implement the
same interface and delegate after applying caching or metrics.

## Provider contract tests

Test empty history, malformed records, duplicate IDs, multiple draws on one day,
timezone normalization, same ID with incompatible rules, source outage, pagination
boundaries and deterministic ordering. Do not run integration tests against a live
service or require private API keys. `SymfonyIntegrationTest` demonstrates replacing
the CSV provider while leaving the injected consumer unchanged.
