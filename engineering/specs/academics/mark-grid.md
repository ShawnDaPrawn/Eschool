# Adaptive mark capture and released-result clients

Build the shared academic screens in packages/eschool_features/academics, using
Material3, the existing core theme/API/session and AdaptiveScaffold. Add teacher
entry points without replacing the existing phone navigation or legacy exam
screens. Families open explicitly released results under their existing profile.

Teachers select allocated group, school year and term, then task. A wide grid
shows learners and question columns with exact decimal input, keyboard Tab/Enter
navigation and bounded rectangular TSV paste from Excel. A narrow screen uses
one learner card with question fields; there is no horizontal-page overflow.
Task/maximum labels, saved/pending state, explicit dispositions/reasons, coverage
and provisional ratings stay readable and accessible. Keep drafts only in memory,
warn before leaving with changes and never save a stale response into another
school/group/task. A disabled/locked/moderated row cannot use a hidden bypass.

Use the server preview formula after debounced changes; the client does not
calculate weighted percentages. Saving sends original revisions and exact decimal
strings. A 409 keeps the unsaved inputs, asks for comparison, and requires an
explicit reload; no blind retry or destructive overwrite. A failed request keeps
the draft, and stale loads/previews are ignored after context changes. Grade heads
correct/moderate with reasons through their own operations. Releasers preview
selected learners and commit the unchanged fingerprint, choosing official or
explicit provisional mode. Expose only server-confirmed capabilities.

Family results show released period/subject/rating/coverage and clearly identify
provisional mode. Never fetch a staff matrix or expose capture/moderation metadata.
No fees condition applies. A school/profile change clears all cached person data.

Automate exact input/paste, bounds and failure atomicity, preview races, revision
conflicts, context switches, keyboard traversal and read-only permissions; widget
checks at phone/tablet/desktop widths and affected-app integration checks. Verify
real HTTP paths and school context. Add the package to CI. Synthetic timing or
widget checks are not actual teacher timing/UX acceptance or full-term pilot proof.
