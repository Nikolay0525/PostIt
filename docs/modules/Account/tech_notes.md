# Account — Tech Notes

## Tech debt
- `UserSettings.show_adult_content`, "no self follow" and "no self block" are business rules that are **not enforced** yet. Add validation in a Service and, where possible, a DB check.
- `UserCounters` are denormalised; no code updating them was found in the reviewed files. Decide between event listeners and DB triggers/jobs.
- Karma formula is undefined. Define it in the specification before implementing.
- `User::isAdult()` reads `date_of_birth`; make sure registration requires and validates this field, otherwise the method fails on null.

## Non-obvious decisions
- Password reset and verification endpoints are throttled (5/min and 6/min). Reset responses are identical for existing and unknown emails to avoid account enumeration.
- `AuthService` depends on `UserRepositoryInterface`, not on Eloquent — keep it that way (policy §4).

## Edge cases
- `UserSettings` and `UserCounters` use the user id as primary key with `keyType = string`; they must be created together with the user (in one transaction).
