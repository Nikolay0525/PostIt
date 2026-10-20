# Privacy Policy — PostIt

| | |
|---|---|
| Name | Privacy Policy — PostIt |
| Version | 0.1.0 |
| Status | Draft (not yet published to users) |
| Classification | Public |
| Last update | 2026-10-05 |
| Owner | Project owner (Mykola Poberezhnyi) |

> This document explains which personal data PostIt collects, why, who can see it, how long it is kept and how it is deleted. It is written to be shown to users (for example on the registration page) and is kept as a separate document from the internal [security policy](security_policy.md). Every new personal data field must be added here before it is implemented.

## 1. Who we are

PostIt is a community discussion platform: users join topic groups, publish posts, comment, vote and follow each other. The controller of the data is the project owner. Privacy requests: *contact address to be published before the public release*.

## 2. Principles

- **Minimisation** — we collect only what a feature needs. We don't ask for a real name, phone number, address or location.
- **Purpose limitation** — data is used only for the purposes listed below.
- **No selling and no advertising** — personal data is never sold, rented or used for ads; there is no third-party analytics or tracking.
- **Transparency** — what is public and what is private is stated in §5.

## 3. Data we collect

| Category | Data | Source | Purpose |
|---|---|---|---|
| Account | Username, email, password (stored only as a bcrypt hash), date of birth, email verification time | You, at registration | Log in, identify you, send verification and reset emails, age check for adult content |
| Profile | Avatar image, status emoji and text, "About me" | You, optional | Show your public profile |
| Preferences | Interface language, languages you speak, theme, content filters (swear words, adult content), "allow messages", "allow non-essential cookies" | You; languages you speak are pre-filled from your browser language at registration | Show the site the way you chose, recommend groups in your languages |
| Activity | Posts, comments, votes, group memberships, follows, shares, which posts you opened, reports you send | Your actions | Run the platform, build your feed ("only new" filter), count karma and achievements |
| Messages *(planned)* | Direct messages you send and receive | Your actions | Deliver the message |
| Technical | Session id, IP address and browser user-agent of the active session, server error logs | Your browser | Keep you logged in, protect against abuse, fix errors |

**We do not collect:** real name, phone, postal address, precise location, payment data, contacts, biometric data, or data from other websites.

## 4. Cookies and browser storage

| Name | Type | Purpose | Lifetime |
|---|---|---|---|
| Session cookie (`<app>-session`) | Essential | Keeps you logged in | Session, 120 min of inactivity |
| `XSRF-TOKEN` | Essential | Protects your forms from forged requests (CSRF) | Same as session |
| `remember_web_*` | Essential, only if you tick "Remember me" | Keeps you logged in after the browser is closed | Until you log out |
| Theme override (localStorage) | Essential (preference) | Remembers a temporary light/dark switch | Until cleared in the browser |

PostIt currently uses **no** non-essential cookies (no analytics, no ads). The "Allow non-essential cookies" setting is saved for the future and is off by default; nothing non-essential will be set unless you turn it on.

## 5. Who can see your data

| Visible to everyone (including guests) | Visible only to you | Visible to staff only when needed |
|---|---|---|
| Username, avatar, status, "About me", join date, followers count, groups you are in, your posts and comments in **public** groups, vote counts (not who voted) | Email, date of birth, settings, languages, which posts you opened, your votes on specific items | Reports you send (to the moderators of that group or administrators); sanctions applied to you |

- Posts and comments in a **private** group are visible only to its members.
- Who voted on a post or comment is never shown, only the totals.
- Moderators and administrators *(planned roles)* see the content of reports and the reason of a ban; they never see your password or email unless needed to answer your request.

## 6. Sharing with third parties

| Recipient | What | Why |
|---|---|---|
| Email delivery provider | Your email address and the text of the verification/reset email | To deliver the email |
| Hosting provider | All stored data, encrypted in transit | Run the server |
| Image moderation service *(planned, FR-MOD-013)* | Uploaded image only, without your account data | Block illegal content |

Data is disclosed to authorities only when required by law. Nothing else is shared.

## 7. Storage and protection

- Data is stored in the PostIt database and file storage on the project server; passwords only as hashes.
- The connection between your browser and the site is encrypted (HTTPS).
- Access to the server and backups is limited to the operator. Security measures are described in the internal security policy.

## 8. Retention

| Data | How long |
|---|---|
| Account, profile, settings | Until you delete the account |
| Posts and comments | Until you or a moderator delete them. A deleted post/comment is hidden and its text is kept marked as deleted so the discussion structure and moderation history stay correct; it is fully removed when the account is deleted |
| Votes, follows, memberships, views | Until you undo them or delete the account |
| Password reset token | 60 minutes |
| Session data (IP, user-agent) | Until the session ends (120 min of inactivity or logout) |
| Server logs | Up to 14 days (daily rotation) |
| Reports and sanctions | As long as needed for moderation history; the retention period is to be defined before Moderation release |
| Backups | Rotated; deleted data disappears from backups within the backup rotation period |

## 9. Your rights

You can:

- **access** your data — your profile and settings pages show it; a full copy can be requested;
- **correct** it — edit your profile and settings at any time;
- **delete** it — deleting the account removes your account, settings, counters, follows, memberships and personal data by the database cascade rules (NFR-PRV-001). Until self-service deletion is available, deletion is done on request;
- **restrict** — turn off direct messages, hide adult content, keep cookies to essential only;
- **object or complain** — contact the project owner, or the Ukrainian Parliament Commissioner for Human Rights (personal data protection authority).

## 10. Age

Your date of birth is used only to check whether you are 18 or older. Users under 18 cannot enable adult content. The date of birth is never shown to other users.

## 11. Changes to this policy

When this policy changes, the version and date above are updated and users are informed on the site before the change takes effect. A change that allows new uses of existing data requires your consent.
