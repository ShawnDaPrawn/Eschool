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

## Private v2 contract

The Growth API exposes staff year/group configuration, paginated allocated
rosters, staff learner views, own-family subject choices and views, published
subject graph reads, optimistic graph writes and queued scoped repair. All eight
operations recheck fresh school/module/role/owner authority, return private
no-store responses, and authorize before idempotent replay. Graph writes accept an
empty edge list, allowing a new graph to begin without fabricated prerequisites
and an existing graph to clear explicitly. A stale revision conflicts.

Family subject selection includes only the learner's current or released
subjects; evidence still requires release and school opt-in. Hidden mark-derived
evidence yields no artificial gap priorities. Nodes include human grade names
for client views. The optional `mastery` context capability is additive; the
module remains off until the school explicitly enables it. No mutation accepts
client-computed scores. Rebuild jobs coalesce pending identical school/group work.

`engineering/contracts/mastery.openapi.yaml` is the owned contract fragment. The
canonical complete contract and pinned generated Dart SDK include the parallel
Phase 4 activity contract. App and panel navigation/views follow separately;
these APIs do not claim completed learner-facing Phase 3 or live acceptance.

## Shared app and panel views

The learner/parent route resolves the signed-in learner or the selected own child
and binds school, reader identity, child and module capability before retrieval.
Staff selects an allocated teaching group, a paginated roster and learner. The
shared Material 3 screen shows up to three root topics and a separate evidence
view; phones have bottom navigation and larger screens a rail. Unknown evidence
is distinct from assessed level zero; school-hidden evidence never becomes an
invented zero count or no-gap claim. Account/school/child changes, backgrounding,
refusals and stale responses clear private state. No mastery cache is persisted.

The native panel adds scoped learner guidance and a curriculum-manager sourced
prerequisite editor. It explains that family evidence opt-in applies to the whole
school and still requires released marks/ownership/approval. Managers can refresh
published anchors, add/remove/clear sourced edges and compare a stale revision
with their escaped submitted text. Ordinary teachers cannot edit sources.
Historical panel contexts hide write controls. Client and panel verification is
recorded separately from human language, physical-device and live acceptance.
The derived mutex is school/subject-wide because a learner projection aggregates
evidence across grades and years. Rebuilds acquire it before any evidence
snapshot and hold it through replacement and aggregate writes. After-commit jobs
share this mutex with standalone verified-evidence aggregate refreshes; source
School/Subject rows are not locked. The initial, unmerged feature migration uses
one mutex row per school/subject. Former group members resolve released target
topics from both retained task and question definitions, so question-only tags
remain visible subject to the current published graph filter.
