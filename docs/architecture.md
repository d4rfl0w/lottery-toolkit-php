# Architecture and compatibility

## Scope and layers

The core under `src/` uses only PHP 8.3 standard facilities. Domain values validate
at construction and expose readonly data. Services are stateless except the
explicit random engine owned by `NumberGenerator`. `DrawProviderInterface` is the
I/O extension point. The core never reads environment variables, a database,
application configuration or network endpoints.

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
application/provider -> DrawImporter -> DrawCollection -> filters -> StatisticsAnalyzer
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

The documented classes in `Rupcom\LotteryToolkit` and exception hierarchy are
public. Constructor/method parameter names are public because PHP supports named
arguments. Readonly property names, map key meanings, ordering and JSON schemas
are contracts. Service classes are final; extend behavior through composition,
providers and an injected `Randomizer`. The enum `FrequencyOrder` is closed.

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
