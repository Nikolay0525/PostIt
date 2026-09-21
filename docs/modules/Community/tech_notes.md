# Community — Tech Notes

## Tech debt
- Models `UserGroupSubscription`, `GroupJoinRequest`, `GroupModerator` declare an **array `$primaryKey`**. Eloquent does not support composite keys natively; `save()`/`find()` on these models will not behave correctly. Use the relation methods (`attach`/`sync`) or add a composite-key package/query-builder access.
- `GroupJoinRequest.status` and `GroupModerator.role` are raw integers with no enum. Create `JoinRequestStatus` and `GroupRole` enums (values to be agreed).
- `members_count` on the group page is dummy data; must come from `withCount('members')`.
- Join / subscribe on the group page is a local toggle only; no server endpoint exists.
- `Group` has no soft delete; deleting a group cascades and destroys its posts.

## Non-obvious decisions
- Private groups are enforced only in the UI right now. The server must reject reads of private-group posts for non-members before release (policy anti-pattern #2).

## Open questions
- Cool-down after a rejected join request.
- Set of moderator roles and their permissions.
