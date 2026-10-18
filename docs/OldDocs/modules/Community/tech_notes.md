# Community — Tech Notes

## Tech debt
- Models `UserGroupSubscription`, `GroupJoinRequest`, `GroupModerator` declare an **array `$primaryKey`**. Eloquent does not support composite keys natively; `save()`/`find()` on these models will not behave correctly. *(0.1.5)* `EloquentGroupRepository::subscribe()`/`unsubscribe()` demonstrate the workaround for `UserGroupSubscription` — `insertOrIgnore()`/a WHERE-scoped `delete()` via `Model::query()`, same pattern as `Vote` in `Content`. `GroupJoinRequest`/`GroupModerator` still need it when their services are built.
- ~~`GroupJoinRequest.status` and `GroupModerator.role` are raw integers with no enum.~~ *(0.1.5, corrected)* Already resolved before this was last touched — both are cast via real enums (`JoinRequestStatus`, `GroupModeratorRole`), just not wired into any service yet. This line was stale.
- *(0.1.5)* Subscribing to a **public** group now has a real server endpoint (`MembershipService`, `POST`/`DELETE /groups/{id}/subscribe`). "Request to join" a **private** group is still a local UI toggle only — `JoinRequestService` doesn't exist yet, tracked separately.
- `Group` has no soft delete; deleting a group cascades and destroys its posts.

## Non-obvious decisions
- Private groups are enforced only in the UI right now. The server must reject reads of private-group posts for non-members before release (policy anti-pattern #2).
- (0.1.1) Guardian entry deliberately draws **randomly** from the qualifying candidate pool instead of offering the role to the top-scoring member, to avoid turning the role into a karma/popularity race.
- (0.1.1) Guardian retention deliberately does **not** reuse `contribution_score` (which is driven by community likes on ordinary posts/comments): a Guardian who spends their time moderating instead of posting must not be penalised for it. A separate `standing_score` is used instead.
- (0.1.1) The Owner's fallback moderation ability is a **live condition** ("does this group currently have zero active Guardians?"), not a one-time flag, so it also covers the rare case where every Guardian of a group decays out at once.
- (0.1.1) An individual Guardian action is never put to a direct crowd vote; only the `Moderation` appeal/jury process can overturn it. This is intentional — see `Moderation` tech notes for why direct voting was rejected.

## Open questions
- Cool-down after a rejected join request.
- (0.1.1) Exact `contribution_score` threshold for the Guardian candidate pool.
- (0.1.1) Exact `standing_score` decay rule, evaluation period and warning grace period.
- (0.1.1) Whether a declined Guardian offer can be re-offered later, and after how long.
- (0.1.1) Whether a long-absent Owner who returns automatically reclaims ownership or must request it back.
- (0.1.1) Exact bounds the platform sets on Owner-configurable parameters (target Guardian ratio, response-time window).
- (0.1.1) Whether Guardian activity should feed the existing `achievements` system (`Engagement`) as a motivator; raised as a promising idea, not yet decided.
