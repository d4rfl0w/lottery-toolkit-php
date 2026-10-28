# Lottery Toolkit for PHP

Niezależna biblioteka PHP 8.3+ do reguł gier liczbowych, importu losowań, statystyk,
sprawdzania kuponów i generowania kombinacji. Pakiet: **rupcom/lottery-toolkit**,
namespace: **Rupcom\LotteryToolkit**, licencja **MIT**.

Główna dokumentacja projektu jest po angielsku: [README](README.md),
[API](docs/api-reference.md), [integracja Symfony](docs/symfony-integration.md).

## Możliwości

- Lotto, Mini Lotto, Eurojackpot i własne gry z niezależnymi pulami liczb.
- Import CSV, JSON i tablic PHP; interfejs własnych providerów.
- Częstotliwości, pary, rozkłady parzystości i sum, historia wystąpień.
- Filtrowanie po datach i analiza ostatnich N losowań.
- Walidowane, niemutowalne kupony z wieloma zakładami i raporty trafień.
- Bezpieczny generator losowy oraz opcjonalny bundle Symfony z autowiringiem.
- Uruchamialna aplikacja przykładowa REST i Console oraz testy kontenera.

Rdzeń nie wymaga Symfony ani innych pakietów Composer. Statystyki opisują historię;
nie przewidują przyszłych wyników. Raport trafień nie jest decyzją o wypłacie ani
obliczeniem kwoty wygranej. Dane przykładowe są syntetyczne.

## Instalacja

Wersja v0.1.0 jest przygotowana do pierwszego wydania, ale nie została opublikowana.
W projekcie korzystającym z lokalnego katalogu biblioteki:

```sh
composer config repositories.lottery-toolkit path ../lottery-toolkit-php
composer require 'rupcom/lottery-toolkit:@dev'
```

Po publikacji będzie można użyć `composer require rupcom/lottery-toolkit:^0.1`.
W samym repozytorium:

```sh
composer install
php examples/quick-start.php
php examples/symfony/bin/console lottery:analyse
composer check
```

## Przykład

```php
<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Rupcom\LotteryToolkit\Game\GameRules;
use Rupcom\LotteryToolkit\Import\DrawImporter;
use Rupcom\LotteryToolkit\Import\DrawRecord;
use Rupcom\LotteryToolkit\Import\Source\InMemoryDrawSource;
use Rupcom\LotteryToolkit\Statistics\StatisticsAnalyzer;

$importer = new DrawImporter();
$draws = $importer->import(GameRules::lotto(), new InMemoryDrawSource([
    new DrawRecord('demo', '2026-01-01', ['main' => [1, 2, 3, 4, 5, 6]]),
]));
$report = (new StatisticsAnalyzer())->analyze($draws);
echo json_encode($report->mostFrequent(3), JSON_THROW_ON_ERROR);
```

Import przyjmuje zawartość CSV/JSON, nie ścieżkę pliku. Daty bez czasu oznaczają
północ UTC. Niepoprawne rekordy powodują wyjątek; nic nie jest pomijane po cichu.
Szczegółowe kontrakty opisuje [przewodnik](docs/getting-started.md).

[Współtworzenie](CONTRIBUTING.md) · [Bezpieczeństwo](SECURITY.md) ·
[Roadmapa](docs/OPEN_SOURCE_ROADMAP.md) · [MIT](LICENSE).
Kod aplikacji Lotto Assistant nie został skopiowany ani objęty nową licencją.
