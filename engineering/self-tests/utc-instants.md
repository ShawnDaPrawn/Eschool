# Stored UTC instants across school timezones

`App\Casts\UtcInstant` represents columns whose stored SQL date-time is UTC.
The setter normalizes offset-bearing strings and DateTimeInterface values to UTC;
the getter parses naive stored values explicitly as UTC rather than the current
school/app timezone. JSON uses ISO UTC, null remains null, and the caller's date
object is not mutated. This is a reusable cast, not a blanket data conversion.
Calendar dates and existing fields deliberately stored in local time must retain
their own semantics. Database TIMESTAMP session settings remain a separate
connection configuration; the real roundtrip fixture uses DATETIME.

Four real MySQL/unit cases pass: UTC, Africa/Johannesburg and America/New_York
roundtrips, offset-crossing midnight, explicit ISO JSON, nullable values and
immutable caller input. A new consultation scheduling test first reproduced a
future UTC opening being read as already open under the school timezone; that
feature now adopts this cast. Its own domain verification is recorded separately.
This focused change introduces the cast and its evidence; adopters must use it
only where their write path already defines UTC storage, with domain regressions.
No production data rewrite, deployment or device qualification is performed.
