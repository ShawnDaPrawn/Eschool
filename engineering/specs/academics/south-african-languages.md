# Afrikaans and isiZulu

Add `af`/`zu` to both existing app catalogues and register them in the panel for
fresh installs and upgrades. English remains the default; do not overwrite an
existing language row or remove configured choices on rollback. Every new locale
file has the complete English key set, including the Phase 2 academic workbenches.
Preserve placeholders, markup, URLs, dates/formats and identifiers exactly.

Shared static academic labels and the mark vocabulary now read the existing app
locale. Keep user-entered names, private Markdown and school-authored report
comments unchanged. Existing layouts and workflows remain shared. Tests without
a locale delegate keep their English fallback. Add scoped widget tests loading
the real assets, and enforce both languages and working school terminology in
both translation suites. Panel tests enforce complete keys, placeholders, native
label serving and idempotent/non-destructive registration.

Draft translations were prepared from public repository UI strings using the
public Google Translate text service, with terminology corrections recorded in
`engineering/languages/glossary.json`. No private build document, learner record,
credential or school message was sent for translation. An offline translation
prototype was rejected for poor terminology and its weights are not distributed.
There is no new runtime translation/model service, key, dependency or spending.

This is a development draft with automated structural review and terminology
corrections. Fluent Afrikaans and isiZulu review, school-champion terminology,
provider/device/staging evidence and production acceptance remain separately
recorded in the review record. Do not claim a native speaker reviewed these files.
Those evidence items do not freeze independent development.
