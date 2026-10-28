# Third-party notices and provenance

The repository's MIT license applies to its original library code, examples,
synthetic fixtures and documentation. It does not relicense external packages,
lottery operator material, trademarks or the existing Lotto Assistant application.

Lotto Assistant's backend declares a proprietary license. It was inspected to
understand functional boundaries; its source was not copied into this project.
See [the source analysis](docs/source-analysis.md). No private configuration,
production database, API key, user ticket or downloaded results archive is included.

There are no Composer runtime dependencies in the core. Development dependencies
are installed into the ignored vendor/ directory and are not distributed as project
source. The resolved inventory was reviewed using `composer licenses`: Symfony,
PHPStan, PHP-CS-Fixer, PSR packages and their associated runtime helpers use MIT;
PHPUnit and associated Sebastian, PHAR IO and tokenizer/parser tooling use
BSD-3-Clause where listed. See [the complete versioned inventory](docs/dependency-licenses.md).
These permissive licenses retain their own copyright and notice requirements.
Do not remove their license files if redistributing dependency code separately.

Built-in game names identify number-selection presets and imply no affiliation or
endorsement. Public operator instructions were consulted to verify selection
ranges; no operator illustrations, text passages, databases or proprietary code
were incorporated. Contributors must document the rights for any future adapted
code or dataset before it is accepted.
