# Changelog

Changes follow Semantic Versioning. Before 1.0, breaking changes require a minor
version; patch releases preserve the documented public API.

## [Unreleased]

### Changed (breaking, unreleased)

- Group public classes into Game, Draw, Import, Ticket and Statistics namespaces.
- Replace `DrawImporter::fromArray/fromJson/fromCsv` with `import(GameRules, DrawSourceInterface)`.
- Introduce immutable `DrawRecord` input DTOs and CSV, JSON and in-memory source adapters.
- Separate CSV syntax and date parsing from import orchestration.
- Split ticket matching into per-bet/per-pool operations and reuse draw counts safely.
- Update Symfony service IDs, examples and documentation for the new API.
- See [migration instructions](docs/architecture.md#migration-from-the-initial-flat-api).

### Fixed

- Duplicate import IDs report the actual conflicting record and stop further reads.

## [0.1.0] — pending first release

### Added

- Immutable game rules, named number pools, draws and multiple-bet tickets.
- Lotto, Mini Lotto, Eurojackpot and custom game definitions.
- Atomic CSV, JSON and PHP iterable imports with strict validation.
- Chronological histories, inclusive date filtering and last-N selection.
- Per-pool frequency, rankings, recency, sum/odd-even distributions and pairs.
- Multiset-safe ticket matching and structured JSON match reports.
- Secure random combinations with an injectable Randomizer.
- Provider interface and optional Symfony bundle, game configuration and DI aliases.
- Executable Symfony REST/Console example and opt-in domain exception handling.
- English-first guides, API reference, Polish introduction and tested examples.
- PHPUnit, PHPStan max, PSR-12 formatting, PHP/Symfony CI and MIT governance files.

No public tag, Packagist submission or publication has been performed.
