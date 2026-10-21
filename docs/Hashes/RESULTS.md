# Laboratory work #3 — computed results

Base string: `2026_SE-23-1_Poberezhnyi_Mykola_варіант:12_`
Length: 43 characters / **50 bytes** (UTF-8; `варіант` = 7 Cyrillic chars = 14 bytes).
Padded with `_` to **72 bytes** → String #1.

Note: bcrypt salts and the 10 random characters (String #3) are random, so hash
values differ on every run. Byte lengths, the verification table and the timing
ratio are stable. Values below are from one sample run.

## Hashes #1–#4 (bcrypt, cost 10)

| # | String (bytes) | Hash (sample run) |
|---|----------------|-------------------|
| 1 | String #1 (72) | `$2y$10$g5azoT0j55HCLDa3ouRnA.cBpzjRd3akDL93d8RCHpsVZWHyG0P/S` |
| 2 | #1 + "1" (73)  | `$2y$10$uBWJL2o/ZOUShW6sWXjHaemb4eNbo8.bs0pyE9/.pzVpbuex6QFcC` |
| 3 | #1 + 10 rnd (82)| `$2y$10$ZrYWdwZJ55Lb4XKZNGs9KuMvXLS7xgkqMvFIVyw4bWpPcrAVYBz0.` |
| 4 | #1 − last (71) | `$2y$10$SjYtpAuBAjVLAVyPpBOvjO8Xf92JMDhcjrr4UksCik0HdYzj4P/oG` |

## Task 7 — pairwise verification (`password_verify`)

|      | H#1 | H#2 | H#3 | H#4 |
|------|-----|-----|-----|-----|
| S#1  | OK  | OK  | OK  |  .  |
| S#2  | OK  | OK  | OK  |  .  |
| S#3  | OK  | OK  | OK  |  .  |
| S#4  |  .  |  .  |  .  | OK  |

Strings #1, #2, #3 share the same first 72 bytes; bcrypt reads only the first
72 bytes, so their hashes are mutually interchangeable (9 "OK" pairs). String #4
is 71 bytes — a byte was removed inside the first 72 — so it matches only H#4.

## Tasks 8, 10, 11

- Task 8 string: `pass: Poberezhnyi`
- Hash #5 (SHA-256): `d9b7549616ddbe3bf9e24e80960ce31dbe8d1709cf2afc7e89fc0e03a9882a13`
- Hash #6 (bcrypt, cost 12): `$2y$12$SqfzuhcvwIschAtn4zQL8OMo3xs6groRP5e9xnwH.7QsGohSpvr1W`

## Task 12 — brute force (dictionary of 20, correct candidate last)

| Target | Algorithm | Elapsed (sample run) |
|--------|-----------|----------------------|
| Hash #5 | SHA-256 | **14.6 µs** |
| Hash #6 | bcrypt, cost 12 | **4 867 341.7 µs ≈ 4.87 s** |

bcrypt is ≈ 330 000× slower per the same 20 candidates. A fast hash (SHA-256)
makes dictionary/brute-force search trivial; bcrypt's deliberately expensive
derivation makes it impractical. Absolute numbers depend on the machine; the
ratio does not.
