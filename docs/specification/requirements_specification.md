# Software Requirements Specification — PostIt

| | |
|---|---|
| Document | Requirements Specification (style: ISO/IEC/IEEE 29148:2018) |
| Version | 0.1.0 |
| Status | Draft |
| Last update | 2026-09-21 |
| Owner | Project owner (Mykola Poberezhnyi) |

## 1. Introduction

### 1.1 Purpose
This document specifies the requirements of **PostIt**, a community discussion platform. It is the primary source of requirements: changes to behaviour are made here first, then in code and tests (specification-driven development, project policy §12).

### 1.2 Scope
PostIt lets people join topic groups, publish posts, discuss them in nested comments, vote, follow authors and earn achievements. Moderators and administrators keep the community safe. The product is a web application (server-rendered by Laravel, interactive UI by Vue via Inertia).

**Out of scope:** native mobile apps, public third-party API, real-time chat, payments.

### 1.3 Definitions

| Term | Definition |
|---|---|
| Guest | Visitor who is not logged in |
| User | Registered account with a verified email |
| Group | Topic community; public or private |
| Member | User subscribed to a group (or approved to a private group) |
| Moderator | Member with a moderator role in a group |
| Administrator | Platform-level operator |
| Post / Comment | Content published in a group / reply under a post or another comment |
| Karma | Reputation score derived from votes on a user's content |

### 1.4 References
- ISO/IEC/IEEE 29148:2018 — Requirements engineering
- ISO/IEC/IEEE 42010:2022 — Architecture description
- ISO/IEC/IEEE 15289:2019 — Content of life-cycle information items
- NIST SP 800-218 — Secure Software Development Framework
- [Project policy](../project_policy.md), [Module registry](../modules.md), [Architecture description](../architecture/architecture_description.md)

## 2. Stakeholders and user classes

| Stakeholder | Interest |
|---|---|
| Guest | Read public content, decide whether to register |
| User | Participate: post, comment, vote, follow, receive notifications |
| Moderator | Keep a group healthy: review reports, ban, approve join requests |
| Administrator | Keep the platform healthy: platform bans, escalated reports, achievements |
| Project owner / developers | Maintainable, well-documented code base |

## 3. Product perspective and constraints

- **C-1** The system shall be implemented with PHP 8.3+, Laravel 13, Inertia 3 and Vue 3.
- **C-2** All entity identifiers shall be UUIDs.
- **C-3** Development shall follow the [project policy](../project_policy.md).
- **C-4** The system shall run on a relational database supported by Laravel (SQLite, MySQL/MariaDB, PostgreSQL).

## 4. Functional requirements

Priority: **M** = must, **S** = should, **C** = could. Status: **Done** (implemented), **Partial** (model/UI exists, no working end-to-end flow), **Planned**. Verification: **T** = test, **I** = inspection, **D** = demonstration.

### 4.1 Account (module `Account`)

| ID | Requirement | Pri | Status | Ver |
|---|---|---|---|---|
| FR-ACC-001 | The system shall allow a guest to register with name, email and password. | M | Done | T |
| FR-ACC-002 | The system shall send an email verification link after registration and allow it to be resent no more than 6 times per minute. | M | Done | T |
| FR-ACC-003 | The system shall allow a registered user to log in with email and password, optionally with "remember me". | M | Done | T |
| FR-ACC-004 | The system shall return the same generic error for any failed login and shall not reveal whether the email exists. | M | Done | T |
| FR-ACC-005 | The system shall allow a user to log out, invalidating the session. | M | Done | T |
| FR-ACC-006 | The system shall allow a user to request a password reset link, limited to 5 requests per minute, with an identical response for existing and unknown emails. | M | Done | T |
| FR-ACC-007 | The system shall allow a user to set a new password using a valid reset token. | M | Done | T |
| FR-ACC-008 | The system shall let a user manage settings: UI language, speaking language, dark theme, swear-word filter, adult-content display, cookies, and whether direct messages are allowed. | S | Partial | D |
| FR-ACC-009 | The system shall allow adult-content display to be enabled only for users aged 18 or older. | M | Planned | T |
| FR-ACC-010 | The system shall let a user follow and unfollow other users, but not themselves. | S | Partial | T |
| FR-ACC-011 | The system shall let a user block and unblock other users, but not themselves, and shall hide blocked users' content from the blocker. | S | Partial | T |
| FR-ACC-012 | The system shall maintain per-user counters (posts, comments, groups joined, reports sent, positive and negative votes) and karma. | S | Partial | T |

### 4.2 Community (module `Community`)

| ID | Requirement | Pri | Status | Ver |
|---|---|---|---|---|
| FR-COM-001 | The system shall let a user create a group with a name (≤ 50 characters), description (≤ 250), rules (≤ 250), language and public/private visibility. | M | Planned | T |
| FR-COM-002 | The system shall show a group page with name, description, rules, member count and visibility to any visitor. | M | Partial | D |
| FR-COM-003 | The system shall let a logged-in user subscribe to and unsubscribe from a public group. | M | Partial | T |
| FR-COM-004 | The system shall let a logged-in user send a join request to a private group and shall store it as pending. | M | Partial | T |
| FR-COM-005 | The system shall let a moderator approve or reject join requests of their group; approval shall create the membership. | M | Planned | T |
| FR-COM-006 | The system shall hide the posts of a private group from non-members, on the server and in the UI. | M | Partial | T |
| FR-COM-007 | The system shall let a group creator assign and remove moderators with a role. | S | Planned | T |
| FR-COM-008 | The system shall ask a guest to log in or register when the guest tries to join or subscribe. | M | Done | D |

### 4.3 Content (module `Content`)

| ID | Requirement | Pri | Status | Ver |
|---|---|---|---|---|
| FR-CON-001 | The system shall let a member publish a post in a group with an optional title (≤ 100 characters) and a required article. | M | Planned | T |
| FR-CON-002 | The system shall generate a slug for every post. | S | Planned | T |
| FR-CON-003 | The system shall let anyone read a post of a public group together with its comments. | M | Partial | D |
| FR-CON-004 | The system shall let a logged-in user comment on a post (≤ 500 characters) or reply to a comment, forming a nested thread. | M | Partial | T |
| FR-CON-005 | The system shall let a guest read comments but not write them, prompting the guest to log in or register. | M | Done | D |
| FR-CON-006 | The system shall let a logged-in user upvote or downvote a post or comment once, and change or remove that vote. | M | Partial | T |
| FR-CON-007 | The system shall order a group's posts by **Newest** (creation time) or **Top** (vote score). | M | Partial | D |
| FR-CON-008 | The system shall show a home feed: trending posts for guests, a personal feed for logged-in users. | M | Partial | D |
| FR-CON-009 | The system shall let an author or a moderator delete a post or comment softly; a deleted comment that has replies shall stay in the thread as "deleted". | M | Partial | T |
| FR-CON-010 | The system shall let a user attach images to content and mark adult images; adult images shall be shown only to adults who enabled them. | C | Planned | T |
| FR-CON-011 | The system shall let a user search posts, groups and people. | S | Planned | D |

### 4.4 Moderation (module `Moderation`)

| ID | Requirement | Pri | Status | Ver |
|---|---|---|---|---|
| FR-MOD-001 | The system shall let a verified user report a post, comment or user with a short text (≤ 100 characters). | M | Planned | T |
| FR-MOD-002 | The system shall route a report to the moderators of the target's group, or to administrators if there is no group. | M | Planned | T |
| FR-MOD-003 | The system shall let a reviewer resolve or dismiss a report with a note (≤ 500 characters) and let a moderator escalate it to administrators. | M | Planned | T |
| FR-MOD-004 | The system shall let a moderator ban a user from a group with a reason (≤ 250 characters) and an optional expiry date. | M | Partial | T |
| FR-MOD-005 | The system shall prevent a user with an active group ban from posting, commenting and joining in that group. | M | Planned | T |
| FR-MOD-006 | The system shall let an administrator ban a user from the platform with a reason and an optional expiry date. | M | Partial | T |
| FR-MOD-007 | The system shall treat a ban with no expiry date as permanent and a ban as active until its expiry date. | M | Done | T |

### 4.5 Engagement (module `Engagement`)

| ID | Requirement | Pri | Status | Ver |
|---|---|---|---|---|
| FR-ENG-001 | The system shall define achievements by a tracked metric, a comparison type and a target value. | S | Partial | I |
| FR-ENG-002 | The system shall track each user's progress towards achievements and mark an achievement completed once its target is reached. | S | Planned | T |
| FR-ENG-003 | The system shall notify a user about relevant events (new reply, decision on join request, report result, achievement unlocked, ban) with a short text (≤ 100 characters) and an optional link. | S | Partial | T |
| FR-ENG-004 | The system shall show unread notifications and let a user mark them as read. | S | Planned | D |
| FR-ENG-005 | The system shall let a user send direct messages (head and body ≤ 500 characters each) to a user who allows messages and has not blocked the sender. | C | Planned | T |

## 5. Non-functional requirements

| ID | Category | Requirement | Ver |
|---|---|---|---|
| NFR-SEC-001 | Security | All passwords shall be stored hashed. | I |
| NFR-SEC-002 | Security | Every state-changing request shall be protected by CSRF and validated server-side. | T |
| NFR-SEC-003 | Security | Access rules (private groups, bans, adult content) shall be enforced on the server, not only in the UI. | T |
| NFR-SEC-004 | Security | Sensitive data (passwords, tokens, emails) shall not appear in logs, events or shared frontend props. | I |
| NFR-SEC-005 | Security | Authentication-related endpoints shall be rate limited. | T |
| NFR-PER-001 | Performance | Feed and group pages shall be paginated and shall not load all posts at once. | D |
| NFR-USA-001 | Usability | The UI shall support left-to-right and right-to-left text (e.g. Arabic, Hebrew) in user content. | D |
| NFR-USA-002 | Usability | The UI shall be responsive from mobile to desktop widths. | D |
| NFR-MNT-001 | Maintainability | Code shall pass Laravel Pint and the test suite before merge. | T |
| NFR-MNT-002 | Maintainability | Every module shall keep `business_logic.md`, `changelog.md` and `tech_notes.md` up to date. | I |
| NFR-PRV-001 | Privacy | Deleting a user shall remove or anonymise the user's personal data according to the database cascade rules. | T |

## 6. External interfaces

- **User interface:** web pages rendered by Inertia + Vue (Home, Group, Post, Login, Register, Forgot/Reset password, Email confirmation).
- **Email:** verification and password reset mail via the configured Laravel mailer (`log` by default in development).
- **Database:** relational database through Eloquent.

## 7. Data requirements

Main entities and their modules are listed in the [architecture description](../architecture/architecture_description.md#4-data-view) and in each module's `business_logic.md`. All identifiers are UUIDs; link tables use composite primary keys.

## 8. Verification

Each requirement has a verification method (T/I/D). Tests are organised as described in project policy §10; a requirement is *Done* only when its verification passes.

## 9. Traceability

| Module | Requirements | Module document |
|---|---|---|
| Account | FR-ACC-001 … 012 | [business_logic](../modules/Account/business_logic.md) |
| Community | FR-COM-001 … 008 | [business_logic](../modules/Community/business_logic.md) |
| Content | FR-CON-001 … 011 | [business_logic](../modules/Content/business_logic.md) |
| Moderation | FR-MOD-001 … 007 | [business_logic](../modules/Moderation/business_logic.md) |
| Engagement | FR-ENG-001 … 005 | [business_logic](../modules/Engagement/business_logic.md) |

## 10. Open issues

- Karma formula, moderator role set, cool-down after rejected join requests and the administrator representation are not defined yet (see module `tech_notes.md`).
