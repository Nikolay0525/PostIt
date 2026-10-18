# Moderation — Tech Notes

## Tech debt
- `Report.status`, `Report.target_type` are raw integers; create `ReportStatus` and reuse `TargetType`. `reports.target_id` is a polymorphic uuid without FK.
- `GroupBan` has an array `$primaryKey` (`group_id`, `blamed_user_id`) — composite-key limitation, see Community notes.
- No service was found that enforces bans: `GroupBan::isActive()` exists, but no check of it was found when posting, commenting or joining.
- Check that `PlatformBan` has an `isActive()` method like `GroupBan`; add one if missing.
- Confirm how platform administrators are represented (no admin flag or role was found in the reviewed files); it is needed for platform bans and escalated reports.

## Non-obvious decisions
- One ban record per (group, user): re-banning updates the existing row (extend `expires_at`) instead of inserting a new one.
- Escalation is modelled by `escalated_at` on `Report`; whether it also needs its own status value is an open question.
- (0.1.1) Jurors are drawn from active Guardians **outside** the appealed action's own group on purpose — a same-group juror knows the parties and is more likely to protect a peer or side with a majority they belong to.
- (0.1.1) Voting is blind (jurors never see each other's vote before resolution) to stop the first vote from anchoring the rest, the same reasoning CS:GO's Overwatch review system used for independent verdicts.
- (0.1.1) The Owner is intentionally **not** run through the same jury process as a Guardian: the jury pool is drawn from Guardians, and the whole point of the Owner role is that it is not community-elevated in that way. Its backstop is administrative, not peer review.

## Open questions
- Appeal process for bans (0.1.1 answers this for Guardian actions specifically; whether the same jury process also covers direct `GroupBan`/`PlatformBan` appeals, or only Guardian content actions, is still open).
- Retention period for resolved reports.
- (0.1.1) Exact panel-eligibility bar for jurors (minimum activity/reliability).
- (0.1.1) Whether content stays removed or is provisionally restored while an appeal is pending.
- (0.1.1) What counts as a "pattern" of appeals against an Owner (count and time window) before a platform administrator is notified.
- (0.1.1) Depends on the existing open item below: platform administrators must be representable in the data model before FR-MOD-012 can be implemented.
