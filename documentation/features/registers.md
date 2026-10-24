# Academic registers

A homeroom register records the morning; a period register records an actual
lesson. Dated enrolments and teaching allocations decide the roster and staff
access. They are distinct: a missed period cannot replace the morning register.

Teachers can capture and correct the school-local current day. A current grade
head can correct earlier days with a reason. Every amendment preserves its actor,
time, source and before/after audit. An offline timestamp does not grant rights
to change a previous day. Keep a rejected queue for review; ask the grade head to
record the correction after comparing it with the current register.

The school sets its absence cutoff and teaching days; the initial cutoff is10:00
on weekdays. Only recorded, current unexcused homeroom absences alert guardians.
Approved leave suppresses that morning notice. A missing register needs staff
follow-up and is never guessed absent. Notifications follow the school rules;
queued delivery and actual parent receipt are tracked separately.

Learner QR codes are signed identifiers containing no name or birth date. A
teacher still needs access to the dated roster to record a scan. Families can
replace their own child's QR code if needed; an old copy is then invalid. Codes
are separate from future card sign-in and payments.

The backend is implemented first. Shared tap/QR/offline screens and the panel
register overview follow in the client slice. Real phone timing, offline/device
rehearsal and school cutoff delivery still need staging/pilot evidence.

Teachers open **Academic registers** from Home, choose the date and allocated
homeroom or lesson, and capture by tap or learner QR. **Mark remaining present** is
an explicit action; missing entries stay unmarked. Save before leaving the screen.
An offline copy queues encrypted changes for retry. A conflict asks you to compare
with the saved register; preparing a reviewed draft does not apply it until Save.
Corrections require a reason. A grade head handles earlier-day corrections.

Administrators use **Academic registers** in the panel to see recorded counts,
missing entries and captured absences, open a register, or set the local alert
cutoff/teaching days. Parents and learners see today's homeroom attendance on Home
and open **Attendance and QR** for their own code and period entries. Replacing a
QR invalidates the previous code; it never grants attendance write access.
