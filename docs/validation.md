# Validation record

Validated locally on **2026-10-28**. No CI badge, remote build result, public release
or Packagist availability is claimed. GitHub Actions is configured to repeat these
checks after the repository is published.

## Tested matrix

Each row used an isolated Composer dependency resolution for its Symfony series
and ran the full `composer check` command inside the stated PHP Docker runtime.

| PHP | Symfony FrameworkBundle / DI / Console | PHPUnit | PHPStan max | PSR-12 / Composer |
| --- | --- | --- | --- | --- |
| 8.3.36 | 6.4.48 | 108 tests, 359 assertions passed | Passed | Passed |
| 8.4.27 | 6.4.48 | 108 tests, 359 assertions passed | Passed | Passed |
| 8.3.36 | 7.4.21 | 108 tests, 359 assertions passed | Passed | Passed |
| 8.4.27 | 7.4.21 | 108 tests, 359 assertions passed | Passed | Passed |

Development tools: PHPUnit 11.5.57, PHPStan 2.3.3, PHP-CS-Fixer 3.95.27.
A supplementary host run on PHP 8.5.4 also passed. This is not certification of
all PHP releases or every historical Symfony 7.x patch. Symfony 8 is not supported.

The matrix helper constrains all participating Symfony components to the selected
series (contracts and polyfills retain their independent version families), rather
than downgrading FrameworkBundle alone while accidentally retaining newer DI/HTTP
components. Resolved dependency metadata is kept in each local test copy's ignored
lock file. CI resolves supported ranges anew.

## Standalone consumer installation

A fresh application installed `rupcom/lottery-toolkit` through a Composer path
repository with `--no-dev`, on both PHP 8.3.36 and PHP 8.4.27. Only the toolkit was
installed. `tools/core-smoke.php` verified import, analysis, matching and generation,
and asserted that Symfony's Kernel class was unavailable. Both runs passed.

## What the suite exercises

- Public values, validation, rule identity, immutability and invalid nested types.
- Strict CSV quoting/header/column rules; JSON/PHP records; calendar/timezone errors.
- Deterministic history order, inclusive filters, last-N and empty histories.
- Exact frequency, recency, rankings, pair, sum and odd/even results.
- Independent pools, repeated-number semantics and multiple bets.
- Secure-default generator invariants and seeded repeatability.
- Real Symfony kernel and compiled DI container, configured/custom providers,
  custom/default games, malformed configuration and missing service references.
- Actual HTTP request handling and Console command execution through shared services.
- The quick-start program and standalone marked documentation examples, each in
  a separate PHP process.

No live lottery service, API key, production database or non-synthetic draw dataset
is involved.

## Packaging and provenance checks

Composer validation passed. Development dependency audits reported no known
advisories or abandoned packages at validation time; this is a point-in-time check,
not a future security guarantee. License metadata was reviewed and recorded in
[the dependency inventory](dependency-licenses.md).

The Composer ZIP was inspected by `tools/check-archive.py`: original source,
LICENSE and documentation are present; vendor dependencies, generated Symfony
configuration/cache, development caches and lock files are excluded. The new
library uses its own local Git repository with no remote or published tag. The
original Lotto Assistant working tree remained unchanged.

## Documentation artifact

`python tools/build-docs.py` with MkDocs 1.6.1 built the complete site successfully
in strict mode, including link validation. The output is local under `site/`.
The workflow uploads a reviewable artifact; it does not publish to GitHub Pages.

## Reproduce

```sh
composer install
composer check
composer audit --locked
composer archive --format=zip --dir=.cache/archive
python3 tools/check-archive.py .cache/archive/*.zip
```

In a **disposable copy**, run `php tools/select-symfony.php '6.4.*'` or
`php tools/select-symfony.php '7.*'`, followed by `composer update` and
`composer check` under each PHP version. The helper intentionally rewrites the
copy's development constraints. Use separate copies or clear the example cache
when switching Symfony versions.
