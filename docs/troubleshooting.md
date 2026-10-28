# Troubleshooting

| Symptom | Cause and action |
| --- | --- |
| Composer cannot find the package | v0.1.0 is not published yet. Use the README's local path installation. |
| Symfony class missing | The bridge is optional. Install FrameworkBundle in the consuming app or use core classes only. |
| Provider interface cannot be autowired | Register a service implementing `DrawProviderInterface`, set `lottery_toolkit.provider`, or add your own alias. |
| GameRegistry service removed from container | Services are private. Inject the registry into a registered consumer instead of fetching it directly. |
| Unknown configuration key | Check the exact spelling in the Symfony guide; the config tree rejects typos. |
| Pool requires integers | JSON strings such as `"7"`, floats and booleans are not integers. Normalize trusted source data explicitly. |
| Expected exactly these pools | Supply every configured pool, with no extras. Eurojackpot uses `main` and `euro`. |
| CSV import cannot find headers | Pass CSV contents, not a filename; check delimiter and header case. |
| Duplicate draw ID | Deduplicate intentionally at the provider boundary, or assign unique IDs for multiple draws on the same day. |
| Date rejected | Use strict YYYY-MM-DD or RFC3339 with seconds and offset. Calendar overflow and relative expressions are not accepted. |
| End-date draw missing | Bounds are instants. Midnight excludes the rest of that day; specify an appropriate timezone and end-of-day bound. |
| Ticket and draw mismatch despite same ID | Their full rules differ, e.g. range or repetition. Use a versioned game definition for historical rule changes. |
| Recency is null | The number was never seen in the selected history. This does not mean it is overdue. |
| No matches because latest() is null | The provider returned an empty history. Handle this before passing a draw to TicketChecker. |
| Random output repeats across bets | Bets are independent; only duplicates within a pool are disallowed by default. |
| API reports HTTP 500 | Import/provider failures are server errors; fix the configured data source rather than changing ticket validation. |
| Example namespace missing | Run composer install with development autoload from this checkout. Examples are not part of the installed API. |
| Memory use grows with archive size | Imports and collections are eager. Bound the input; streaming analysis is a roadmap item. |
| Container cache uses a different Symfony version | Clear only the example's generated `examples/symfony/var/cache/` after switching dependencies. Matrix jobs use isolated copies. |

When reporting a problem, include PHP/Symfony versions, the command used and a
minimal **synthetic** reproduction. Never attach real account data, private tickets,
API credentials or production environment files. Use private reporting for
security vulnerabilities as described in [SECURITY.md](../SECURITY.md).
