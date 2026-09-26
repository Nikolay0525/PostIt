# Moderation — Changelog

Append-only. Newest entries first. Format: `## [YYYY-MM-DD] [TICKET] Title`.

## [2026-09-26] [DOCS] Appeal/jury process; Owner accountability backstop

- Designed the appeal flow for a Guardian's own moderation action: filed only by the affected user (not a direct crowd vote), a neutral case file, an independent random cross-group jury (3 standard / 5 severe panel), blind voting, automatic escalation on a non-unanimous verdict.
- Added `ModerationAppeal` and `AppealVote` (planned entities).
- Verdicts privately feed the Guardian's accuracy record and each juror's own reliability record (`Community`), used to filter future jury pools.
- Added rule versioning as a requirement (FR-MOD-011): an appeal is judged against the rules in force when the action happened.
- Designed the Owner accountability backstop: a *pattern* of appeals against an Owner (not a single one) reaches a platform administrator, who can warn and, if repeated, force an ownership transfer (`Community` `OwnershipTransferred`).
- Added FR-MOD-008 … 012 (see requirements specification 0.1.1).

## [2026-09-21] [INIT] Initial module documentation

- Documented reports, group bans and platform bans.
- Proposed the `ReportStatus` lifecycle and the report-to-sanction flow.
