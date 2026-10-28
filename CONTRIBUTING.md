# Contributing

Thanks for helping make the toolkit useful to PHP developers. English is the
primary language of code, issues, reviews and documentation. Polish documentation
is supplementary. No contribution requires a paid API, private database or lottery
account.

## Local workflow

1. Fork or create a branch from the current main branch.
2. Install PHP 8.3+ and Composer 2, then run `composer install`.
3. Reproduce the behavior with a small synthetic fixture and a focused test.
4. Implement the change and update the English docs and changelog.
5. Run `composer cs:fix`, then `composer check`.
6. Open a pull request describing the behavior, tests and compatibility impact.

The core must remain framework independent. Symfony integration belongs under
`src/Bridge/Symfony/`; runnable application examples belong under `examples/`.
Use strict typing, PSR-12, immutable domain values and precise collection PHPDoc.
PHPStan runs at max with no baseline. Do not suppress an error to conceal an
invalid type or weaken validation to accommodate malformed input silently.

## Public API changes

Parameter names matter for named arguments. Preserve map keys, sorting, empty
history semantics and exception categories unless a planned compatibility change
is documented. Prefer a small, understandable API to new abstraction layers.
Discuss new provider interfaces, rule models and new runtime dependencies before
investing in a large implementation. Patch releases must preserve compatibility;
pre-1.0 breaking changes require a minor release and migration notes.

## Tests and fixtures

Test meaningful behavior and edge cases, not only implementation details. Cover
new input formats with malformed records, quotes, empty data, dates and integer
boundaries. Exercise Symfony changes through the compiled container and the real
example kernel. Use `<!-- executable -->` for standalone documentation blocks so
`ExamplesTest` runs them in separate PHP processes.

All fixture data must be synthetic or have explicit redistribution rights. Do not
copy third-party result archives, credentials, user tickets or production logs.
For dependency-version testing use `php tools/select-symfony.php '6.4.*'` or
`php tools/select-symfony.php '7.*'` in a disposable checkout, then Composer update.
The script intentionally modifies development constraints in that checkout.

## Contribution rights and review

Submit only work you have the right to contribute under this repository's MIT
license. Keep required third-party notices and disclose provenance for adapted
code. This policy does not relicense dependencies or grant rights to lottery data.
Explain meaningful tool-generated changes and verify their correctness and rights
as you would any other contribution. Do not invent contributors or endorsements.

Maintainers may request smaller changes, additional tests or a clearer contract.
Use the issue and pull request templates. Follow the [code of conduct](CODE_OF_CONDUCT.md).
Security reports use the private path in [SECURITY.md](SECURITY.md), not public issues.
