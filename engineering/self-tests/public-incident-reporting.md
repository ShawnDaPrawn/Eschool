# Public anonymous reporting — 25 October 2026

The real phone-friendly `/school/{slug}/report` flow now supports anonymous text
reports, private code recovery of the reporter's thread, immutable follow-up
retries and older replies. Emergency guidance appears before submission and on
follow-up; there is no acknowledgment gate. Public requests ignore browser
account identity. They run outside session/cookie/Sanctum middleware, retain no
browser storage and carry private codes only in POST bodies. The code is created
cryptographically, shown for private saving and stored only as a keyed digest.

The page reuses the established local Material 3 tokens, components and Poppins
fonts. It loads no third-party resources or JavaScript. Its enforced CSP allows
only local styles/fonts and same-origin form actions, with framing disabled.
Inputs and report/thread bodies render escaped. Errors and conflicts return a
bounded draft privately, without redirecting or copying it into session flash.
A dedicated exception renderer is registered before Laravel's default validation
redirect; the first regression run reproduced the unsafe 302, then passed after
this correction. Public labels use English while restoring the caller's locale;
new Afrikaans/isiZulu catalogue keys are explicit English fallbacks pending fluent
and clinical-language review.

Actual verification:

- Public page/API/translation/controller cohort: 20 tests / 35,174 assertions.
- Full Larastan: zero errors; changed PHP formatted with Pint; TypeScript and
  whitespace checks pass. Focused final public/CSP/locale results recorded below.
- Actual Chromium workflow at 390px and 1280px: 2 tests passed in 5.6 seconds.
  Submit, reply and reopen with code pass. No cookies, localStorage or
  sessionStorage are created; no off-origin requests or code-bearing URLs occur.
  Serious/critical axe, unique element IDs and horizontal overflow checks pass.
- Combined wellbeing/Support/session-roster SDK wire checks: 9 tests pass. The
  optional Support chooser query fix is preserved without losing wellbeing DTOs.

The first browser attempt correctly received a private generic 500 because
Laravel's reloading development server dropped injected Docker DB variables.
The harness now uses `--no-reload`; no application assertion was weakened.
The test runtime uses only the assigned synthetic database and was stopped after
verification. No production, store, real-school response process or physical
mobile-device acceptance is inferred.

Attachments, identified app reporting, the restricted staff panel/app and generic
wellbeing notices continue next. This public increment does not claim the entire
wellbeing workflow or Phase 4 complete. Ingress/body-log privacy and live response
procedures remain external acceptance checks.

Final focused public/CSP/locale cohort passes 4 tests / 55 assertions after the
Material 3 asset integration; changed-file Pint and whitespace checks pass.
