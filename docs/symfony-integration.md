# Symfony integration

## Install the optional bridge

The bridge is part of `rupcom/lottery-toolkit`. The core still has no Symfony
runtime dependencies. In an existing Symfony application, install the library
using the [current installation instructions](../README.md#installation). The
application needs `symfony/framework-bundle` in a supported 6.4 or 7.x version;
Console examples additionally use `symfony/console`. YAML configuration requires
`symfony/yaml`; PHP configuration does not.

Register the bundle in your application's `config/bundles.php`:

```php
return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Rupcom\LotteryToolkit\Bridge\Symfony\LotteryToolkitBundle::class => ['all' => true],
];
```

Keep your existing bundles. There is no published Flex recipe and no automatic
modification of your application. The explicit bundle registration is required.

## Configuration reference

```yaml
# config/packages/lottery_toolkit.yaml
lottery_toolkit:
    provider: App\Lottery\MyDrawProvider
    default_game: lotto
    map_exceptions: false
    games:
        office_draw:
            pools:
                main:
                    count: 3
                    min: 1
                    max: 20
                    allow_duplicates: false
```

| Option | Default | Contract |
| --- | --- | --- |
| `provider` | `null` | Existing service ID implementing `DrawProviderInterface` |
| `default_game` | `lotto` | Built-in or configured game ID, injected as `GameRules` |
| `map_exceptions` | `false` | Opt in to generic HTTP 422 responses for invalid domain input |
| `games` | empty | Map of custom definitions; an existing ID intentionally overrides a preset |
| `games.<id>.pools` | required | Non-empty map of named pools |
| `count` | required | Integer 1–100; cannot exceed range when repetition is disabled |
| `min` | `1` | Positive integer |
| `max` | required | At least min, at most 1,000,000; range contains at most 10,000 values |
| `allow_duplicates` | `false` | Boolean; independent per pool |

Unknown keys, invalid rule definitions and an unknown default game fail container
compilation. No provider is registered by default: configure one or define the
interface alias yourself before injecting it. Missing provider services fail normal
container compilation; a provider with the wrong PHP type fails constructor typing.

The executable example uses equivalent PHP configuration:
[lottery_toolkit.php](../examples/symfony/config/packages/lottery_toolkit.php).

## Services and autowiring

The bundle registers `DrawImporter`, `StatisticsAnalyzer`, `TicketChecker`,
`NumberGenerator` and `GameRegistry` with their FQCNs as private service IDs.
`GameRules` aliases `lottery_toolkit.game.<default_game>`. Built-in and configured
games are available from `GameRegistry::get($id)`. `DrawProviderInterface` aliases
your configured service. Configure and register that service in your application:

```yaml
# config/services.yaml, alongside your existing services
services:
    App\Lottery\MyDrawProvider:
        autowire: true
        arguments:
            $path: '%kernel.project_dir%/data/draws.csv'
```

That example assumes a provider with the same constructor as the included
[CsvDrawProvider](../examples/symfony/src/CsvDrawProvider.php). Inject it by the
interface into consumers. The complete
[LotteryService](../examples/symfony/src/LotteryService.php) injects
`DrawProviderInterface`, `GameRules`, `StatisticsAnalyzer` and `TicketChecker`.
For multiple provider implementations, bind the interface per consumer using
Symfony service arguments instead of trying to use multiple global aliases.

## Executable application

From the repository root:

```sh
composer install
php examples/symfony/bin/console about
php examples/symfony/bin/console lottery:analyse
php examples/symfony/bin/console lottery:analyse --bets='[{"main":[1,3,5,7,9,11]}]'
php -S 127.0.0.1:8080 -t examples/symfony/public examples/symfony/public/index.php
```

Use another terminal:

```sh
curl http://127.0.0.1:8080/statistics
curl -X POST http://127.0.0.1:8080/tickets/check \
  -H 'Content-Type: application/json' \
  -d '{"bets":[{"main":[1,3,5,7,9,11]},{"main":[1,2,3,4,5,6]}]}'
```

The statistics endpoint returns `drawCount: 3`. Matching against the latest
synthetic draw returns main-pool counts 6 and 3. The command's `--bets` option
accepts the JSON array itself; the HTTP endpoint accepts an object containing it
under `bets`. Both call the same service. Console errors produce exit status 1.

The example kernel boots FrameworkBundle and LotteryToolkitBundle, loads PHP
configuration, imports three local CSV draws and uses attribute routes. Composer's
**development autoload** maps its namespace. Run it from this checkout after a
normal `composer install`, not from a production `--no-dev` installation. To adapt
it to your own app, copy the example classes into your own namespace and configure
the file path; never depend on the example namespace in production.

The local example runs in debug mode with a public demonstration secret and no
session or authentication requirements. It is a runnable integration example,
not a production deployment template. Choose production infrastructure, error
logging, authentication and request limits in your application.

## Domain failures

`map_exceptions: true` registers `DomainExceptionSubscriber`:

| Failure | Result |
| --- | --- |
| `ValidationException`, `GameMismatchException` | HTTP 422, `{"error":"invalid_lottery_data"}` |
| Malformed JSON in example controller | HTTP 400 through Symfony's normal exception handling |
| `ImportException` / other server errors | Unchanged Symfony error handling, normally 500 |

Internal exception messages are not copied into the bundle's JSON response.
Mapping is global to these exception classes when enabled; disable it to implement
application-specific error translation. Database/CSV availability errors must not
be misreported as invalid client tickets.

## Tests and compatibility

`tests/SymfonyIntegrationTest.php` boots a real kernel and compiled DI container,
issues HTTP requests, runs a discovered command with `CommandTester`, replaces a
provider through the test container, tests custom/default games and validates
configuration failures. Standalone container tests cover default aliases and the
opt-in subscriber. The [validation record](validation.md) records actual tested
versions; the CI matrix repeats PHP 8.3/8.4 against Symfony 6.4 and 7.x.

There is no claim of Symfony 8 compatibility. Earlier 7.x patch releases are not
individually certified; the 7.x CI job resolves the newest eligible 7.x versions.
