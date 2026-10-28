# Security policy

## Supported versions

The first release, v0.1.0, is being prepared and has not been published. Once
released, security fixes are intended for the latest 0.1.x patch. Older development
snapshots are not separately maintained. This is a community-maintained project;
there is no guaranteed response time or commercial support commitment.

## Reporting a vulnerability

Use the repository's **Security → Report a vulnerability** private reporting form
once the public repository exists and maintainers have enabled that feature.
If the form is unavailable, use a private contact channel explicitly listed on a
maintainer's public profile to arrange disclosure. Do not post exploit details,
credentials, private tickets or production logs in public issues. If no private
contact is available, open only a neutral request for a private reporting channel.

Include affected versions, a minimal synthetic reproduction, expected/actual
behavior and the likely impact. Coordinate a public advisory with maintainers
after assessment and a fix. Maintainers should confirm receipt, reproduce the
issue privately and agree on a disclosure timeline appropriate to the impact.

Before the first public release, maintainers must enable private vulnerability
reporting and verify the reporting channel. No unverified email address is listed.

## Application boundary

The core does not fetch URLs, execute commands, store credentials or verify draw
authenticity. It processes in-memory input, so applications must set file, request,
record-count and concurrency limits. Providers own safe file paths, HTTP timeouts,
authorization and credentials. Random generation uses a secure engine by default;
applications that inject predictable engines change that guarantee.

The optional Symfony exception subscriber hides domain messages in its 422
responses. Normal application error handling remains responsible for server
failures. Run production applications without debug exception pages.
