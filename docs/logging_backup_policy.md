# Logging, Monitoring, Backup and Recovery — PostIt

| | |
| --- | --- |
| Status | Active |
| Last updated | 2026-11-19 |
| Stack | Laravel + Inertia + Vue |
| Owner | Nikolay0525 |

This document records PostIt's operational-security decisions: what is logged and
how, what is monitored, how data is backed up, and how the system is recovered.
It is intentionally concise; the rules it refers to are enforced in code
(logging channel, middleware, event listeners) and in `scripts/backup.sh` /
`scripts/restore.sh`.

## 1. Logging

**Channels.** Diagnostic logs go to the default `stack` channel, whose members are
set by `LOG_STACK`: `single` in local development, `daily` in production. Security- and
audit-relevant events go to a dedicated `security` channel (`config/logging.php`),
written as **structured JSON**, one object per event, kept 90 days (longer than
the 14-day diagnostic log) and separated so it can be shipped or protected on its own.

**Record structure.** Every record carries the timestamp (UTC, from the
framework), the level, the message/event name, and a context object. Context
includes the acting subject (`user_id`), the source (`ip`, `user_agent`), the
object of the operation where relevant, the result, and a **`request_id`** shared
across every record of one HTTP request (`AssignRequestId` middleware), so related
events can be correlated — the attribute the Libraxis application log lacked.

**Levels.** INFO — normal significant events (login, logout, content created);
WARNING — unusual but non-failing situations (failed login, access denied);
ERROR — a failed operation; CRITICAL — a condition threatening the whole service.

**Events logged.** Authentication success and failure and logout (implemented via
`LogSuccessfulLogin` / `LogFailedLogin` / `LogLogout`); and, as they are extended,
access-denied (403), email verification, role/permission changes, administrative
actions, and creation/deletion of critical content. Failed logins are logged
**with their source** precisely so credential-guessing is visible — the gap that
made the Libraxis attack invisible in its application log.

**Sensitive data.** Passwords, full access/refresh tokens, client secrets, private
keys and other long-term authenticators are **never** written to a log. Only the
attempted login identifier (email) is recorded on a failed login, never the
submitted password. A log must not become a second store of secrets.

**Monitoring.** Indicators worth watching: the rate of failed logins (a spike
signals credential guessing), the rate of HTTP 500 responses (signals a software
fault or injection), 401/403 rates, request latency and error rate, and
disk/CPU/memory. The `security` channel is the primary source for the auth
indicators.

**Rotation and protection.** Daily rotation; diagnostic logs kept 14 days, the
security log 90 days. The application only appends to logs; for production they
should be shipped to a separate collector so they survive compromise of a single
node and cannot be erased to hide tracks.

## 2. Backup

**Scope.** Backed up: the **database** (all persistent data) and **uploaded files**
(`storage/app` — avatars and images that cannot be regenerated). Not backed up
because they are reproducible from source: `vendor/`, `node_modules/`, framework
caches and built front-end assets. The `.env` secrets and `APP_KEY` are handled
separately as secrets (needed to decrypt restored data, but never backed up
alongside it in clear).

**Strategy.** A **daily full** backup (the database is small, so a full copy keeps
restore simple); if data volume grows, a weekly full plus daily incremental.
Following the **3-2-1** principle: at least three copies, on two independent
storage media/locations, with at least one kept off the main system on isolated,
non-overwritable credentials so an attacker who reaches the main server cannot
destroy the backups with it.

**Retention / location / integrity.** Daily backups retained 14–30 days, stored
off-host; each archive carries a **SHA-256 checksum** verified before any restore
(`restore.sh` refuses an archive whose checksum does not match).

**RPO / RTO.** Recovery Point Objective ≈ **24 hours** (daily backup — at most a
day of new posts/comments lost; the DB dump can run more often to lower it).
Recovery Time Objective ≈ **2–4 hours** — recreate the environment from source,
restore the database and uploads, redeploy.

## 3. Recovery procedure

1. Contain the incident (block the source, take the app to maintenance mode).
2. Verify the backup's integrity (`scripts/restore.sh` checks the SHA-256 first).
3. Recreate the database and restore it and `storage/app` from the latest good backup.
4. Restore secrets/config (`.env`, `APP_KEY`) from secret storage.
5. Fix the root cause before returning to service (e.g. patch the vulnerability).
6. Verify not just that the process starts, but that authentication and the main
   data operations work, within the RPO/RTO targets.

The procedure is reproducible (`scripts/backup.sh`, `scripts/restore.sh`) and must
be tested periodically: a backup that has never been restored is not proven to work.
