# Subject choice and pathway planning

The owner retained the Build Bible's Grade 9 subject-choice guide, sourced
admission reference data and projected admission score. This feature extends the
existing school, guardian, subject and released-marks boundaries. It does not
predict an institution's admission decision or write academic marks.

Each school appoints holders of `pathways-manage` to maintain its reference
catalogue. Every published programme or subject-choice guide requires an HTTPS
source URL, source title, source year, review date and expiry date. The school
owns verification against that source; records identify the reviewing staff
account internally. No invented institution entries or universal scoring policy
are seeded. Source revisions are immutable and audited; withdrawal removes the
record from fresh family use. Historical revisions are retained. Expired sources
cannot support a new projection. The Bible catalogue module `pathway` is separately switchable, off by
default, and does not activate external AI or payment providers.

Programme rules explicitly record required subjects/minimum percentages, which
subjects count, the source's score bands, number of best subjects to count,
excluded subjects and minimum total. All percentages use integer basis points.
Scoring uses the published rule, including its stated exclusions; Life
Orientation is never silently included or excluded. Unknown marks remain unknown
and cannot become zero or a passing score. A complete score requires all relevant
chosen candidates to have marks; incomplete calculations show known evidence and
missing subjects separately. Meeting a published minimum is labelled as such,
with no offer or admission guarantee.

An approved current learner and their current guardian may read current school
references and use released own-child marks for an explicitly chosen school
year/period, or enter a clearly labelled hypothetical scenario. Hypothetical
marks and selections are calculated transiently, never persisted or applied to
school marks. A Grade 9 guide carries sourced explanatory text, compulsory
subjects and bounded elective groups; the client shows valid combinations and
links to the source. No learner records are exposed through staff reference
maintenance. An ordinary teacher, facilitator, unrelated parent, other school,
platform account or impersonation cannot manage reference data or read another
learner's projection.

The responsive manager panel creates and revises references through labelled
fields, publishes/withdraws explicit revisions and shows review/expiry state.
The existing learner/Parent app gets the same shared private planner, current
child and source selection, guide and projected-score evidence. Source/context
changes and app background erase private marks and scenarios; late responses
cannot restore them. Read-only schools can calculate without persisting data.

Verification includes source validation/versioning/idempotent publication,
expiry and withdrawal, integer boundary bands, unknown marks and exclusions,
released-only family access, wrong role/school/owner, inactive guardian/learner,
platform and impersonation exclusion, schema/route contracts, SDK reproduction,
phone/desktop widget coverage, real learner/Parent browser journeys and manager
Playwright at 390/820/1280 pixels. Unrun school source review, fluent wording,
physical devices, staging and production acceptance remain separately recorded.
