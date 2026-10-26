# Recognition browser fixture isolation

The integration browser run on `a625946` kept its direct PHP server alive and
passed 340 cases; the first recognition case failed in its CLI fixture before
opening the UI. That log does not contain the CLI exception's stdout, so its
precise database state is not established by the artifact alone.

The old fixture borrowed the first legacy learner in a homeroom. A disposable
MySQL reproduction moved that demo learner's enrolment start one day after the
school-year start. The unchanged fixture then failed in
`RecognitionAuthority::placement` while creating a house membership from the
year start. It therefore depended on another workflow's dates and learner choice.

The corrected fixture owns a dedicated synthetic learner, verified guardian and
recorded consent, active school memberships and its own complete dated enrolment.
Activation follows that guardian consent. A database-name guard confines this
setup to the existing isolated test databases. Other demo enrolments are not
rewritten, and all recognition application policies and browser assertions remain
unchanged.

Verification: old-fixture reproduction failed with the missing placement; the
corrected fixture succeeded while that borrowed demo row remained unsuitable.
The complete house-point reversal and private award nomination/review/assembly/
presentation/correction workflow passes at 390, 820 and 1280 pixels together
(1.4 minutes), including privacy, accessibility and overflow assertions. Pint and
whitespace checks pass. Full integration CI must still qualify the corrected head.
