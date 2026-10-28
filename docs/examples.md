# Examples

All included draw results are synthetic. Runnable examples need no API key, remote
service or database. From the checkout root run `composer install` first.

| Example | Run | Demonstrates |
| --- | --- | --- |
| [Quick start](../examples/quick-start.php) | `php examples/quick-start.php` | Import, last N, statistics, matching and generation |
| [Symfony application](../examples/symfony/src/Kernel.php) | `php examples/symfony/bin/console lottery:analyse` | Bundle, DI, CSV provider and Console |
| [REST controller](../examples/symfony/src/LotteryController.php) | See the [Symfony guide](symfony-integration.md) | HTTP JSON, matching and domain error mapping |
| [README examples](../README.md) | Copy a marked block into a PHP file | Every key core operation |
| [Custom provider](custom-providers.md) | Copy the full example | Replaceable data access |

The test suite executes the quick-start file in a fresh PHP process. It also
extracts documentation blocks marked `<!-- executable -->`, executes each in its
own PHP process from the repository root, and fails on errors or warnings. Keep
such blocks standalone and include `require 'vendor/autoload.php';`. Other snippets
illustrate configuration or partial API calls and are labelled by their context.

## Repeating custom pools and deterministic tests

<!-- executable -->
```php
declare(strict_types=1);

require 'vendor/autoload.php';

use Random\Engine\Mt19937;
use Random\Randomizer;
use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Game\NumberPool;
use Rupcom\LotteryToolkit\Ticket\LotteryTicket;
use Rupcom\LotteryToolkit\Ticket\NumberGenerator;

$game = new GameRules('repeat_demo', [new NumberPool('main', 3, 1, 2, allowDuplicates: true)]);
$generator = new NumberGenerator(new Randomizer(new Mt19937(42)));
$ticket = $generator->generate($game, bets: 2);
$validated = new LotteryTicket($game, $ticket->bets);
if (count($validated->bets) !== 2) {
    throw new RuntimeException('Expected two valid bets.');
}
```

A seeded engine is useful for tests; omit it for secure default randomness.
Repeating pools are unordered multisets, not ordered digit games.
