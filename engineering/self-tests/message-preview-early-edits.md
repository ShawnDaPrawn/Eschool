# Message preview early-edit guard

The latest hosted parent-deposit tree failed one panel browser case: an unsaved
subject edit could precede deferred message-script initialization and leave the
saved-preview submit button enabled. The guard now compares current controls with
their server-rendered defaults when it initializes, including checkboxes, text
areas and selects. Existing input/change guards and server revision/fingerprint
checks remain in place.

Five real Chromium cases passed locally without retries: a controlled edit before
script loading, full draft/preview/approval/publication/withdrawal at1024 and1280px,
another teacher/school refusal and archiving an audience after contact revocation.
Existing accessibility, overflow and browser-error assertions remain. Exact-source
hosted CI/review is still required before merge. No production activation occurred.
