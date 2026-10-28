# Source application analysis and provenance

The existing Lotto Assistant workspace was inspected read-only before designing
the API. Its `backend/composer.json` declares a **proprietary** license. The new
library was implemented independently from the functional requirements. No source
files, credentials, environment files, database records or private tickets were
copied. The application was not modified.

| Area inspected | Existing mechanism | Library decision |
| --- | --- | --- |
| `GameCatalog` | Associative configuration for game counts, ranges, prices and schedules | New immutable GameRules/NumberPool; only selection rules included |
| `DrawResult`, repository, `DrawStorage` | Doctrine persistence, game/date lookup, latest sorting and upserts | Persistence stays outside the core; DrawCollection and provider interface |
| `OfficialDrawImporter` | API-specific payload normalization, game mapping, history retrieval and rate-limit retries | No API connector copied; strict generic CSV/JSON/array imports |
| `AiNumberSuggestionService` | Main/special frequency maps and hot/cold ranking plus suggestion strategies | Independent descriptive frequency/pair/distribution analysis; no prediction features |
| `TicketController` | Count, range and uniqueness checks in request handling | Constructor validation shared by draws, tickets and generator output |
| `TicketChecker` | Per-line intersections and potential-win heuristic | Multiset-safe per-pool counts; no payout or potential-win heuristic |
| `TicketStats` | Application ticket counts grouped by status/game | Application dashboard aggregation stays outside this library |
| `NumberGenerator` | Shuffled ranges and application-specific line limits | Secure Randomizer default and explicit validation limits |
| Ticket storage, OCR, notification services | User/application workflows and side effects | Excluded from the independent package |

Reusable ideas are validated number selections, separating main/special pools,
counting historical occurrences, comparing tickets with draws, and mapping source
records. The proprietary implementation itself is not reused or relicensed.

Scope intentionally excludes account state, official API credentials, purchases,
prices, schedules, automated result downloads and monetary prize verification.
The existing heuristics are not an authoritative payout model.

Preset selection ranges were cross-checked against the operator's public game
instructions: [Lotto](https://www.lotto.pl/lotto/jak-grac),
[Mini Lotto](https://www.lotto.pl/mini-lotto/jak-grac), and
[Eurojackpot](https://www.lotto.pl/eurojackpot/jak-grac). These references do not grant
rights to redistribute their results databases. Historical rule versions need
separate custom definitions.
