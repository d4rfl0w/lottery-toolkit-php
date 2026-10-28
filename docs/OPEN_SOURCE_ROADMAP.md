# Three-month open source roadmap

This is a proposed roadmap, not a promise of volunteer capacity. The three months
start with the first public release. Prioritize concrete user reports and reviewed
contributions over adding integrations without maintainers.

## Month 1: publish a dependable foundation

- Review and tag v0.1.0, submit the package to Packagist and enable private security
  reporting after verifying project ownership and contact channels.
- Run the documented PHP/Symfony matrix on every change and publish the English
  documentation site after reviewing its build artifact.
- Triage real user feedback on game/pool naming, importer schema and error messages.
- Label small, scoped issues: malformed CSV cases, fixture improvements, provider
  contract examples and documentation clarifications.
- Define an API-change review checklist and publish migration notes for any change.

Exit criteria: reproducible installation, passing CI, runnable Symfony examples,
a functioning reporting channel and clear documentation of the supported contract.

## Month 2: practical extension points

- Evaluate a streaming JSON Lines importer with bounded memory and explicit
  partial-failure semantics; do not weaken atomic existing imports silently.
- Add provider adapter examples for application-owned PDO/Doctrine storage. Only
  implement official remote adapters when API permission, licensing and a
  maintainer are established. Test them with synthetic HTTP fixtures.
- Add games where the existing independent unordered-pool model is correct, with
  documented rule versions and tests. Propose a separate model before adding
  unequal bet/draw sizes or ordered games.
- Benchmark pair analysis on synthetic large histories before designing a
  streaming accumulator or optional pair-analysis switch.

Exit criteria: measured performance notes and one reviewed extension solving an
actual contributor use case, with docs and tests.

## Month 3: ecosystem feedback and API stabilization

- Explore a Laravel service provider as an optional adapter, keeping framework
  dependencies out of the core. Require a runnable application test, as for Symfony.
- Consider percentile summaries, gap distributions and statistical exports with
  clearly documented descriptive semantics; no winning-number predictions.
- Review whether provider/query abstractions need date-range pushdown without
  changing the semantics of already-materialized DrawCollection filtering.
- Evaluate API stability for 0.2 or a future 1.0 based on usage, not elapsed time.
- Document maintenance workload and invite real maintainers through normal review;
  no invented contributor lists, endorsements, stars or usage counts.

Exit criteria: a compatibility review, evidence-backed next-release scope and
an updated roadmap reflecting actual contributions.

## Open source support programs

If appropriate after publication, investigate **OpenAI Codex for Open Source** and
**Anthropic Claude for Open Source**. Before applying, check each program's current
official availability, terms and criteria. No current eligibility or acceptance
is asserted here, and no application has been submitted.

Prepare only verifiable evidence: public MIT source, actual releases, CI results,
security/contribution policies, real issues and pull requests, useful documentation
and an honest maintenance plan. Describe how development tooling would help
maintainers deliver tested fixes. Do not manufacture activity or imply that a
brand-new library automatically qualifies.
