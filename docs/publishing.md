# Preparing and publishing v0.1.0

The repository is prepared locally. No remote repository, tag, push, release or
Packagist submission has been created by this task. Commands below are a maintainer
runbook for a later, explicitly authorized publication.

## Before publication

1. Review the public API and the independent-source provenance in
   [source-analysis.md](source-analysis.md). Confirm ownership of the new original
   project and the intended copyright holder in LICENSE.
2. Create the intended public repository named `lottery-toolkit-php` under the
   maintainer's verified account or organization. Do not assume a GitHub owner name
   from the Composer vendor name. Add verified homepage, source and support URLs
   to composer.json after those destinations exist.
3. Enable issues and private vulnerability reporting, and verify the private
   contact path described in SECURITY.md. Choose branch protection and reviewers.
4. Run the full CI matrix, the standalone consumer job, `composer check`,
   `composer audit --locked` and `python tools/build-docs.py`. Review license
   metadata with `composer licenses` and check the archive for unwanted files.
5. Change CHANGELOG.md's pending 0.1.0 heading to the real release date. Review
   documentation status wording and exact tested dependency versions. Do not add
   an explicit `version` field to composer.json: VCS tags supply the version.
6. Commit the reviewed files, then create a signed or annotated `v0.1.0` tag at the
   tested commit. Push the branch and tag only when publication is authorized.
   Create a GitHub release with factual notes derived from CHANGELOG.md.

## Packagist

Follow [Packagist's submission instructions](https://packagist.org/about).
Verify the intended `rupcom` vendor namespace can be used by the maintainer.
Submit the public VCS repository URL through Packagist's submission form; its
composer.json identifies the package as `rupcom/lottery-toolkit`. Configure the
supported GitHub/Packagist update integration for new tags. Keep any integration
credentials in repository/organization secret storage, never in this package.

Once indexed, test in a fresh application:

```sh
composer require rupcom/lottery-toolkit:^0.1
```

Repeat the standalone smoke test without Symfony, then install into a supported
Symfony app. Confirm PSR-4 loading, LICENSE presence and clean installation with
`--no-dev`. The library's lock file is intentionally ignored: consumers resolve
their own dependency graph; CI exercises supported ranges.

## Documentation publication

`mkdocs.yml` and `tools/build-docs.py` build the English documentation plus the
root project files. The workflow `.github/workflows/docs.yml` only builds and
uploads a Pages artifact. It does not deploy. After choosing the real repository
and URL, enable GitHub Pages with Actions and add a protected deployment job using
GitHub's Pages deployment action, `pages: write` and `id-token: write` permissions,
and the `github-pages` environment. Set the correct site URL for project hosting.
No automatic deployment has been enabled in this local task.

## Future releases

Use SemVer as documented in [architecture](architecture.md). An optional later
release workflow may run on reviewed tags and create draft release notes; it must
not bypass tests or fabricate changes. A patch release preserves the documented
API. Pre-1.0 incompatible changes require a minor release and migration notes.
