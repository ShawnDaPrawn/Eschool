# Translation review record

Scope: Afrikaans (`af`) and isiZulu (`zu`), both existing apps and the panel.
Status on 24 October 2026: **complete development drafts; fluent review pending**.
English stays the default. Structural tests and Codex terminology corrections
are automated/engineering evidence, not fluent-speaker acceptance.

| Evidence | Afrikaans | isiZulu |
| --- | --- | --- |
| Full English key coverage | Automated suite | Automated suite |
| Placeholders and markup | Automated suite | Automated suite |
| School vocabulary | Working glossary; fluent check pending | Working glossary; fluent check pending |
| Fluent reviewer name/date and asset hashes | Pending | Pending |
| Education terminology and mark/register/report/message tasks | Pending | Pending |
| Phone/device and staging acceptance | Pending | Pending |

Review the actual JSON files in `e-school/assets/languages`,
`e-school-teacher/assets/languages`, and `admin-panel/resources/lang`. Compare
with their English files and use `glossary.json` for consistent school terms.
Pay particular attention to guardian approval, refusal/negation, absent/excused/
missing marks, locked terms, corrections, released/provisional results and
withdrawn messages. Review long labels at phone width and both desktop widths.

Correct translations in the locale assets and glossary together, run both apps'
`test/translations_test.dart` and the panel's `SouthAfricanLanguagesTest`, then
record reviewer name/date, affected files, exact asset hashes and any accepted
terminology notes here. Keep real school/user examples private. A later change
to reviewed strings needs a scoped follow-up review; unrelated development
continues. Production acceptance is still outstanding.
