# Integrity, Distribution Model and Anti-Tampering — PostIt

| | |
| --- | --- |
| Status | Active |
| Last updated | 2026-10-21 |
|Stack| Laravel + Inertia + Vue · |
| Owner | Nikolay0525 |

This document records how PostIt protects the integrity of data the user can
influence, which distribution model it follows, and how that model is protected.
It is intentionally concise; the rules it refers to are enforced in code
(middleware, Form Requests, model casts and the framework).

## 1. Data integrity — what must be verified

PostIt is a server-side web application: the database on the server is the single
source of truth, and the client is treated as an untrusted environment. Integrity
protection therefore means never trusting data that the user can influence, and
verifying it on the server.

| Data the user can influence | Risk if modified | Protection |
|------------------------------|------------------|------------|
| Session / authentication cookie | Impersonation, session forgery | Laravel encrypts **and** signs cookies; a tampered cookie fails its MAC and is rejected |
| One-time action links (email verification) | Replay, verifying another account, expiry bypass | Signed URLs (`signed` middleware) — changing the id, hash or expiry breaks the signature |
| Request parameters, form fields, JSON bodies | Invalid or malicious state, privilege escalation | Validated server-side via Form Requests; identity/authorization derived from `Auth::user()` and Policies, never from client-sent ids or roles |
| Uploaded files (avatars) | Malformed/non-image content, path traversal, overwrite | `mimes` checks file **contents**; stored under a server-generated UUID name; size and dimensions capped |
| Direct-message content at rest | Tampering in the data store | Encrypted with the authenticated encrypter (see `encryption_policy.md`) |
| Compliance with usage preconditions (e.g. verified email) | A crafted request bypasses a UI-only check | Enforced server-side with the `verified` middleware on protected actions |

A plain hash (MDC) is not used as the sole integrity control for
client-influenceable data, because anyone holding the data can recompute a valid
hash; and a shared-secret MAC is avoided on the client, because the key would
live in the user-controlled environment. Where tamper-evidence is needed for
client-held artifacts, the framework uses keyed signatures (signed cookies and
signed URLs) whose key never leaves the server.

## 2. Distribution model

PostIt is distributed as **SaaS** (Software as a Service): the application runs on
the server and the user interacts through the web client. The critical logic and
data never leave the server, which is the strongest protection against reverse
engineering and tampering — there is no distributed executable or license file to
analyse, patch or copy.

## 3. Protecting the distribution model

- **Critical logic stays server-side.** The client renders UI and sends requests;
  it never carries the authoritative rules, so it cannot be patched to grant
  itself access.
- **Server-side authorization, deny-by-default.** Every protected action is
  authorized on the server (Policies, middleware), regardless of what the client
  UI shows or sends. Hiding a control in the UI is not a protection mechanism.
- **Usage preconditions enforced on the server.** Actions that require a verified
  account go through the `verified` middleware, so a hand-crafted request cannot
  skip a condition the UI enforces.
- **Rate limiting** bounds automated abuse of public and write endpoints.
- No client-side secrets: the client holds no long-term key whose exposure would
  compromise the service.
