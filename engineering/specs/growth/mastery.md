# Mastery graph and evidence

Learners see up to three topics to work on and a short root-gap explanation;
teachers see current allocated learners' topic evidence. Records are learning
support, not a permanent profile label or promotion decision. The backend engine
and contracts land before shared app/panel views in a follow-on PR.

Use school/subject-scoped stable curriculum topic UUID anchors with published
source references. Explicit sourced prerequisite edges may cross grades but never
schools/subjects. Reject cycles, self-links, private draft references and bounded
size violations. A curriculum manager maintains the graph; ordinary teachers
read only their allocated subject/grade/year context. Do not invent a CAPS
progression from topic sort order. Imports retain source/version provenance.

Staff evidence uses canonical current published question definitions and actual
recorded question scores, excluding missing/absent/excused scores. Family evidence
uses frozen question scores from released canonical mark snapshots, never raw
captured/unreleased rows. Explicit school opt-in is also required for mark-derived
family mastery. Family reads use fresh own-child/guardian approval/departure checks.
Changing the setting or withdrawing approval suppresses reads immediately.

Background recomputation reacts to mark, release and published-definition events,
within the original school. It replaces derived evidence for the affected
subject/grade/year, handles retries and revisions without duplicate evidence,
and updates bounded recent-evidence aggregates. Missing evidence means unknown,
not level zero. Level 0–4 and confidence basis points have an explicit versioned
heuristic; confidence is evidence sufficiency, not a calibrated probability.
Prerequisite traversal returns at most three weak/unknown root topics with paths;
it never fabricates a score for an unassessed topic.

Practice reviews and tutor outcomes will add verified source adapters when those
features land; no public endpoint accepts a learner's self-reported mastery score.
The shared service for Phase 4 is `MasteryService::priorities(User,int learnerId,
int subjectId):array` with the same authorization as the view. Matching peers is
supervisor-mediated; peers cannot enumerate one another's mastery.

Test published/draft, role/school/owner, cyclic/cross-subject edges, revoked role,
canonical task versions, immutable release versus later raw edits, source removal,
replay, unknown evidence, exact integer thresholds, root paths and family opt-in/
approval/guardian withdrawal. Private no-store API contracts and generated SDK are
coordinated with the parallel Phase 4 stream; the backend alone does not claim app
or staging UX acceptance.

The derived mutex is school/subject-wide because a learner projection aggregates
evidence across grades and years. Rebuilds acquire it before any evidence
snapshot and hold it through replacement and aggregate writes. After-commit jobs
share this mutex with standalone verified-evidence aggregate refreshes; source
School/Subject rows are not locked. The initial, unmerged feature migration uses
one mutex row per school/subject. Former group members resolve released target
topics from both retained task and question definitions, so question-only tags
remain visible subject to the current published graph filter.
