# Architecture and compatibility

## Scope and layers

The core under `src/` uses only PHP 8.3 standard facilities. Domain values validate
at construction and expose readonly data. Services are stateless except the
explicit random engine owned by `NumberGenerator`. `DrawProviderInterface` is the
I/O extension point. The core never reads environment variables, a database,
application configuration or network endpoints.

## Module layout

`src/` is the Composer PSR-4 source root. Classes are grouped by responsibility:

```text
src/
├── Game/          # Rules, pools and game registry
├── Draw/          # Validated draws, histories and provider contract
├── Import/        # Import orchestration, input record and source contract
│   ├── Source/    # CSV, JSON and typed in-memory adapters
│   └── Internal/  # Strict date parsing and CSV syntax validation
├── Ticket/        # Tickets, generation, matching and match results
├── Statistics/    # Analysis, reports and ranking order
├── Exception/     # Shared domain exceptions
└── Bridge/Symfony/# Optional framework integration
```

`Game` has no dependency on import formats or frameworks. `Draw` depends on game
rules. `Import` adapts external data into validated draws. `Ticket` and `Statistics`
consume domain objects; neither parses input formats. The Symfony bridge composes
these modules and owns service-container integration.

`DrawSourceInterface` is the input extension point (Strategy/Adapter).
`CsvDrawSource` and `JsonDrawSource` decode their own formats into immutable
`DrawRecord` DTOs; `InMemoryDrawSource` adapts an iterable of those DTOs.
`DrawImporter::import()` depends on that contract and handles date/domain
validation, duplicate detection and atomic collection creation. A new format
requires a new source implementation, without edits to the importer. Parsing
helpers under `Import\Internal` are implementation details, not public API.

`DrawProviderInterface` serves a different purpose: it supplies an already
validated history and owns external I/O. A provider can use the importer or build
`Draw` and `DrawCollection` directly. In-memory domain objects do not need to pass
through the import layer.

`TicketChecker` keeps game compatibility, per-bet matching and per-pool matching
in small methods. Draw occurrence counts are computed once and copied per pool
and bet, preserving multiset semantics. There is only one matching policy, so no
extra strategy hierarchy is required there. Immutable values and constructor
injection in application services provide composition without interfaces for every
class or framework-shaped layers in the core.

`src/Bridge/Symfony/` contains an optional bundle and an exception subscriber.
Composer PSR-4 loading is lazy: these classes are not loaded by core operations.
Symfony packages are development dependencies and suggestions, not runtime
requirements. The consuming Symfony application owns its framework dependencies.
This single-package bridge avoids a second release cycle for a small integration;
a separate package can be considered if adapters acquire independent dependencies.

The bundle follows Symfony's documented
[AbstractBundle configuration mechanism](https://symfony.com/doc/6.4/bundles/extension.html).
The example application is a real FrameworkBundle kernel. Both HTTP controllers
and the Console command call one autowired `LotteryService`; neither duplicates
the statistical or ticket logic. Bundle services stay private and are consumed by
DI. Tests expose only the specific services they need through the test container.

## Data flow

```text
source -> DrawRecord -> DrawImporter -> DrawCollection -> filters -> StatisticsAnalyzer
                                                |
LotteryTicket --------------------------------- Draw -> TicketChecker -> MatchResult[]
GameRules / NumberPool -> validate every draw and bet
GameRules -> NumberGenerator -> LotteryTicket
```

Games use unordered, independent pools. Complete rules determine compatibility.
A history owns its rules even when empty. Collections reject duplicate IDs rather
than silently replacing history. Matching repeating pools uses multiset counts,
so a drawn occurrence cannot be reused by multiple copies in a bet.

No prize amount, legal payout decision, draw schedule, purchase, account, OCR,
notification, AI suggestion, scraper, database entity or production credential is
part of this library.

## Public API and SemVer

The documented classes in the modules under `Rupcom\LotteryToolkit` and the
exception hierarchy are public. `Import\Internal` is excluded from that contract. Constructor/method parameter names are public because PHP supports named
arguments. Readonly property names, map key meanings, ordering and JSON schemas
are contracts. Service classes are final; extend behavior through composition,
providers, sources and an injected `Randomizer`. The enum `FrequencyOrder` is closed.

`StatisticsReport`'s constructor is marked `@internal`: obtain instances from
`StatisticsAnalyzer`. All documented report fields and methods are public.
Symfony lifecycle hooks are framework integration details; bundle name, extension
alias, configuration keys and core service IDs are supported integration contracts.
The example application namespace is educational code, not a reusable library API.

Version `0.1.0` is the first release target. Patch releases preserve this API.
Before 1.0, breaking changes require a minor version and migration notes; they must
never appear silently in a patch release. Starting at 1.0, incompatible changes
require a major version. Deprecate before removal where feasible, test existing
examples, and document changes to validation rules as compatibility changes.

## Resource and correctness tradeoffs

Collections and importers are eager to keep validation atomic and semantics clear.
Bounds on pools prevent accidental unbounded enumeration, but callers still own
limits on record counts, file size and concurrent analysis. O(K²) pair analysis is
intentional and documented. No ambient clock is used in reports or match results;
results depend only on the supplied inputs. Date-only text is always UTC, avoiding
a hidden dependency on PHP's default timezone.

See [analysis of the source application](source-analysis.md) and
[license provenance](../THIRD_PARTY_NOTICES.md).

## Migration from the initial flat API

This is a breaking change to the unreleased API. Consumers of the initial checkout
must update imports and Symfony service IDs; no legacy class aliases are installed.
If the previous API has been released downstream, ship this as a new minor version,
not a patch.

| Previous namespace | New namespace | Classes |
| --- | --- | --- |
| `Rupcom\LotteryToolkit` | `Rupcom\LotteryToolkit\Game` | GameRules, GameRegistry, NumberPool |
| `Rupcom\LotteryToolkit` | `Rupcom\LotteryToolkit\Draw` | Draw, DrawCollection, DrawProviderInterface |
| `Rupcom\LotteryToolkit` | `Rupcom\LotteryToolkit\Ticket` | LotteryTicket, NumberGenerator, TicketChecker, MatchResult |
| `Rupcom\LotteryToolkit` | `Rupcom\LotteryToolkit\Statistics` | StatisticsAnalyzer, StatisticsReport, FrequencyOrder |
| `Rupcom\LotteryToolkit` | `Rupcom\LotteryToolkit\Import` | DrawImporter |

`Bridge\Symfony` and `Exception` namespaces are unchanged. Replace importer calls:

| Previous call | Replacement |
| --- | --- |
| `$importer->fromCsv($game, $csv, ';')` | `$importer->import($game, new CsvDrawSource($csv, ';'))` |
| `$importer->fromJson($game, $json)` | `$importer->import($game, new JsonDrawSource($json))` |
| `$importer->fromArray($game, $rows)` | `$importer->import($game, new InMemoryDrawSource($records))`, with an iterable of `DrawRecord` objects |

Construct each record explicitly with `new DrawRecord(id: 'a', date: '2026-01-01',
numbers: ['main' => [1, 2, 3, 4, 5, 6]])`. CSV/JSON wire formats, matching results
and statistical report schemas remain compatible. Invalid scalar constructor
arguments produce native type errors; invalid record dates/numbers are rejected
by the importer. Duplicate errors now identify the actual conflicting record and
stop consuming the source immediately. Rebuild the consuming application's
Symfony cache after updating service IDs.
