# Alumni and volunteer pool

Action Plan 5.6, shared pools of people and alumni. Owner role: Backend.
School-scoped in this step: no school shares or sees another school's pool.

## User stories

- As the school office (people-pool-manage), I invite former learners from our
  own leaver records, and outside volunteers, into our pool, saying whether
  each offers career talks, job shadows or both.
- As the person invited, I sign in with the starting password the school gave
  me, choose my own password, read the pool consent and decide whether to join.
  Nothing happens for me until I consent, and I can withdraw at any time.
- As the vetting lead (people-pool-vet), I record each person's safety checks:
  the outcome (cleared, refused or revoked), which checks were done, who
  checked, the check date and when the clearance ends.
- As the office, I book cleared people into an activity group session as a
  career talk speaker or job-shadow host. The session is always a group
  session with a member of staff allocated.
- As a pool member, I see my upcoming bookings, and on the day of a session
  the session's attendance list, so I know who is in the room. I never see
  learner records, marks or contact details.

## Rules

- **Consent is the person's.** Inviting creates a dedicated pool account
  (`password_change_required`) and a pool row in `invited`. Consent is only
  recorded by the pool sign-in, when the person accepts the current consent
  text (its version is the SHA-256 of the English text, so changed wording
  asks again). The school cannot give consent for anyone.
- **Former learners** come from the school's own leaver records: a Student
  membership at this school that went through the leaver state (`leaver` or
  `ended`, with `leaver_until`). The school's date of birth must show they are
  18 or older. The learner account is never converted: the alumnus gets a new
  adult account linked to the leaver record by `learner_id`, once per leaver.
- **Accounts.** The email address must be new; existing staff, family or
  learner accounts are not turned into pool accounts (as for vendors).
- **Vetting before anything.** Clearing needs every check in
  `people_pools.required_checks` (identity, National Child Protection Register,
  National Register for Sex Offenders), an expiry in the future and at most
  `maximum_vetting_months` (36) after the check. Only the kind of check is
  stored, never certificate or reference numbers. Every decision is an
  immutable `people_pool_vettings` row and audited. Refusing or revoking
  cancels upcoming bookings.
- **Booking.** The person must have consented, be cleared today and on the
  session date, and offer that kind of session. The session must be upcoming
  and not cancelled, its group active, with at least
  `people_pools.minimum_learners` (never below 2) learners on the session date
  (the same dated roster as session attendance) and a staff allocation
  covering the date. A one-to-one booking is refused.
- **What a pool member sees.** Their profile (school, consent, vetting status
  and expiry), their upcoming bookings (group, session name, venue, date and
  times; no learners), and on the session date only, the session attendance
  list: learner names and recorded status, no ids. The list is refused before
  and after the day, when the clearance has lapsed, when the session was
  cancelled, or when the group has shrunk below the minimum.
- **Withdrawal** sets `withdrawn`, cancels upcoming bookings and revokes every
  pool token at once. A token that somehow survived finds no consent and is
  refused. Signing in again after withdrawing is refused; rejoining is a new
  invitation (not in this step).

## Access

Pool accounts have no school membership and no role. They sign in through
`POST /api/v2/pool/sign-in` (school slug and credentials, like the facilitator
sign-in) and receive a `people-pool-app` token whose ability binds it to that
school. `PoolMemberContext` is the only way in: it accepts only that token,
sets the school from it and refuses an `X-School-Id` naming another school.
`SetSchoolContext` refuses a pool token on every other signed-in route, and
without a membership no school route would accept the account anyway. Every
pool member request re-reads the account, consent, password and policy state.
Web portal sign-in is refused (app only).

Staff routes use `auth:sanctum`, `school.context`, `role.any`,
`module:people_pool` and the permissions above, rechecked on idempotent
replay after the school lock.

## Data changes

`people_pool_members`, `people_pool_vettings`, `people_pool_bookings` and
`people_pool_operations`, all school-owned (SchoolOwnedTables). Vettings and
bookings reference members by `(school_id, pool_member_id)`, so a row can never
point at another school's person. Permissions `people-pool-manage` and
`people-pool-vet` go to Super Admin. Operation receipts keep record ids only;
the invite's request hash binds the starting password by HMAC without keeping
it. The rollback refuses while any row exists.

## Endpoints

| Method | Path | Who |
| --- | --- | --- |
| GET, POST | `/api/v2/people-pool/members` | people-pool-manage |
| GET | `/api/v2/people-pool/members/{member}` | people-pool-manage (vetting notes only for people-pool-vet) |
| POST | `/api/v2/people-pool/members/{member}/vetting` | people-pool-vet |
| GET, POST | `/api/v2/people-pool/bookings` | people-pool-manage |
| POST | `/api/v2/people-pool/bookings/{booking}/cancel` | people-pool-manage |
| POST | `/api/v2/pool/sign-in` | public, throttled |
| GET | `/api/v2/pool/session`, `/api/v2/pool/bookings`, `/api/v2/pool/bookings/{booking}/attendance` | pool token |
| POST | `/api/v2/pool/consent/withdraw`, `/api/v2/pool/sign-out` | pool token |

Writes take an `operation_id` (UUID); a replay returns the same record and a
different request with the same id is refused with 409.

## Module switch

`people_pool` (Futures product module), off by default, depends on `groups`.
Off: staff and pool member reads and writes are refused; withdrawal and
sign-out stay available.

## Bookings through groups, not consultations

Consultations are polls and surveys: they have no session, roster or
attendance list, so a booking cannot rest on them. A school can still use a
consultation to ask which talks learners would like before booking one.

## Seam for sharing between schools

`BookablePeople` is the one place bookings find bookable people; today it is
`SchoolPoolPeople`. Cross-school sharing of vetted people will build on the
shared-records pattern: a platform-level shared person with a per-school pool
row (each school's own consent view and vetting decision), resolved by another
`BookablePeople` implementation. Bookings, attendance and the pool member
profile need no change. The leaver-state `AlumniInvitations` hook is unchanged:
invitations stay a deliberate office action.

## Acceptance tests

`tests/Feature/People/PeoplePoolsTest.php`: happy path from invitation to the
attendance list on the day; alumni from the school's own adult leaver records;
unvetted, refused, expired and revoked people cannot be booked; a one-to-one
or unstaffed session is refused; a pool token reads no learner record, roster,
profile, context or staff route; wrong school (School B cannot list, read, vet
or book School A's pool, and a School A token cannot act at School B); wrong
role (teacher, parent, manage without vet); withdrawal removes access at once;
idempotent invite, vetting, booking and cancellation with id-only receipts.
`ApiRouteTenancyTest` guards the five pool member routes;
`OpenApiContractTest` covers every route.

## Not in this step

Panel pages and app screens; notifications to pool members; re-inviting
someone who withdrew; linking an existing account (for example a parent who
volunteers); cross-school sharing.
