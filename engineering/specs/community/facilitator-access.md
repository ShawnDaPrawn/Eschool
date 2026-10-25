# Facilitator access and restricted shell

A facilitator build is a native identity, never a server role. Reuse the shared
teacher/family activity and message features behind a separate sign-in and a
restricted shell. Leave teacher startup and existing v1 profiles intact. Vendor
and unknown/mismatched identities remain unavailable.

Sign-in names a school code and credentials. Resolve that exact active school,
verify credentials, then require fresh dated Teacher, Facilitator, Counsellor or
Safeguarding school membership. No TeacherModel lookup or Super Admin fallback.
Never issue an app token or cookie session before required password change and
explicit current-version policy acceptance are complete. A credential-validated
409 describes remaining setup and readable policy text. Completing setup resends
credentials and accepted version hashes, checks them under a user lock, and
changes password/records acceptance/issues the session atomically. A failed or
stale setup cannot partly accept policies, change a password or leave a session.

Facilitator uses a separate token/app/portal profile. Signing in/out does not
revoke teacher or parent app tokens or clear their push registration. Fresh
session/configuration returns only authorized school choices and implemented
feature flags. Generic v2 role groups reject a facilitator profile after role,
account, password or school-policy revocation, including switching into another
school whose policy has not yet been accepted.

The new shell initializes no teacher Firebase, notifications, TeacherModel,
Hive-backed legacy auth, teacher routes or teacher repositories. Its session and
private data stay in memory; OS termination requires sign-in again. Use the
existing Material 3 theme and actual scoped groups/messages screens, school
selection and sign-out. Background/resume and account/school changes hide private
content until fresh server authority resolves. Counselling appears only when its
real restricted module exists; platform/impersonation access remains excluded by
SensitiveRecordsAccess before case loading and case assignment policy.

Native Android compilation and a browser smoke must exercise the facilitator
entrypoint after it is enabled. iOS configuration/source checks do not establish
an Xcode/device build; no new Firebase app/store/paid service is provisioned here.

Verify wrong credentials, wrong/currently-ended role, other school, stale policy,
default password, atomic setup, cookie refusal cleanup, profile-specific token
revocation, unchanged teacher push registration, disabled module and stale session
responses. Exercise the restricted menu and real group/message flow on phone and
Chrome and document remaining external qualification honestly.

API contract: engineering/contracts/facilitator.openapi.yaml adds three operations
under /facilitator. Sign-in is public and throttled; session refresh requires
school context and a facilitator profile. Sign-out binds the expected account
and current app token, and remains available after school-role revocation.
Cookie sign-in records successful authentication on the canonical request as
well as a cloned FormRequest, preserving the cleanup middleware invariant.

Every refresh increments the in-memory session epoch and recreates private
readers even when the returned account/school/features match. Old clients and
late unauthorized replies cannot mutate a replacement session. Only current
server capabilities produce menus; support/case capabilities will be separate
from group ownership and never inferred from teacher records.
