# Cryptographic Strategy — PostIt

| | |
| --- | --- |
| Status | Active |
| Last updated | 2026-10-22 |
|Stack| Laravel + Inertia + Vue · |
| Owner | Nikolay0525 |

This document records the cryptographic decisions for PostIt: what is protected,
when it is encrypted and decrypted, which primitives are used, and how keys are
managed. It is intentionally concise; the rules it refers to are enforced in
code (model casts, configuration and the framework encrypter).

## 1. Sensitive data and what is protected

| Data | Sensitivity | Decision |
|------|-------------|----------|
| Direct message content (`messages.head`, `messages.body`) | High — private between two users | Encrypted at rest |
| TOTP / MFA shared secret (planned) | High — authentication secret | Encrypted at rest when MFA ships |
| Passwords | High | Not encrypted — stored as a bcrypt **hash** (one-way), see `access_control.md` |
| Session data, remember tokens | Medium | Server-side session store; not application-encrypted |
| Public content (posts, comments, profiles, group data) | Public by design | Not encrypted — encryption would add no value and break search/indexing |

Only data that is genuinely confidential and not meant to be readable from a
database dump is encrypted. Public content is deliberately left in clear so it
stays searchable and indexable.

## 2. When encryption and decryption happen

Encryption is applied at the **application layer**, not as a one-off call:
- **On write** — a sensitive attribute is encrypted before it reaches the
  database, via Laravel's `encrypted` cast on the model.
- **On read** — it is decrypted transparently when the model is loaded and the
  attribute is accessed.
- Data **in transit** is protected separately by HTTPS/TLS, which is a different layer from at-rest encryption.

## 3. Primitive and mode

Encryption uses Laravel's encrypter, configured as **AES-256-CBC with an
HMAC-SHA256 authentication tag** (`config/app.php` → `cipher`). This is
**authenticated encryption**: every ciphertext carries a MAC, and decryption
fails if the MAC does not verify, so tampered data is rejected rather than used.

This choice is deliberate. A bare cipher mode such as AES-CTR provides
confidentiality but **no integrity**: an attacker who knows a plaintext and its
ciphertext can recover the keystream and forge a different ciphertext that
decrypts to attacker-chosen data, without ever knowing the key. PostIt therefore
never uses an unauthenticated cipher/mode for stored data — only the
authenticated encrypter — and never implements its own crypto.

## 4. Key management

- A single 256-bit application key (`APP_KEY`) is the encryption key, generated
  with `php artisan key:generate` and stored in `.env`.
- `.env` is never committed (it is in `.gitignore`); the key never appears in
  source code, shared configuration or logs.
- **Rotation.** To rotate, generate a new `APP_KEY` and keep the old one in
  `APP_PREVIOUS_KEYS` so existing ciphertext still decrypts; re-encrypt the
  affected records, then drop the old key. Different purposes (at-rest
  encryption vs. signing) use separate keys/derivations rather than one key for
  everything.
- A failed decryption (`DecryptException`, e.g. a MAC mismatch) is treated as
  invalid or tampered data and is not used.

## 5. Related documents

- `access_control.md` — identification, authentication, authorization (passwords are hashed, not encrypted).
