# Module Registry

Entry point to the map of PostIt modules (bounded contexts). Every module has a folder in `docs/modules/<Module>/` with `business_logic.md`, `changelog.md` and `tech_notes.md`.

| Module | Responsibility | Depends on | Status |
|---|---|---|---|
| [Account](modules/Account/business_logic.md) | Registration, authentication, email verification, password reset, user profile, settings, counters, follows, user blocks | SharedKernel | Auth implemented; rest modelled (models + migrations) |
| [Community](modules/Community/business_logic.md) | Groups, public/private access, membership, join requests, moderator roles | Account, SharedKernel | Modelled; UI on dummy data |
| [Content](modules/Content/business_logic.md) | Posts, nested comments, votes, images, feed and sorting | Account, Community, SharedKernel | Modelled; UI on dummy data |
| [Moderation](modules/Moderation/business_logic.md) | Reports, group bans, platform bans | Account, Community, Content | Modelled |
| [Engagement](modules/Engagement/business_logic.md) | Achievements, notifications, direct messages (inbox) | Account, Community | Modelled; menus in UI are stubs |
| [SharedKernel](modules/SharedKernel/business_logic.md) | Base entity with UUID identity, language reference data, shared enums | — | Partially implemented |

## Dependency direction

```
Moderation → Content → Community → Account → SharedKernel
Engagement → Account / Community → SharedKernel
```

Cross-module references go through identifiers (UUIDs) and explicit service calls, never through deep embedded models. A new module or a change of module boundaries requires an update of this registry.
