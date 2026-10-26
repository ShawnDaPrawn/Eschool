# Teaching assistant and task-insight apps

Classic Teacher home/settings/deep routes and the dedicated Facilitator Teacher
menu open separate private teaching and test-insight workspaces. Current feature
availability and dated server teaching allocations are required. A Facilitator
flavour or menu entry grants no Teacher role or extra academic access.

Teachers select current published content, a draft language and appropriate
learner/task/topic evidence. Three-level materials, report comments, practice
questions and script proposals have editable lines. Edited revisions must be
saved before approval. Practice publication and recording official marks require
separate explicit confirmation; an unknown question mark blocks confirmation.
Approved copies can be exported with unique draft/revision filenames; answer
copies are explicitly labelled teacher copies. Insights keep missing evidence
unknown and make no model request.

Generation receipt recovery uses the original UUID without a second compute.
Human review and approved-photo upload recovery keep the exact original intent.
Allowance refusals have a readable 429 response; known unavailable/invalid errors
use the documented nullable error envelope. No automatic provider retry occurs.

Private text, unsaved definitions and decoded images disappear on suspension,
context replacement or denial. A system picker retains only opaque locators;
it rechecks school, actor, source and learner authority after resume before
showing selected pixels. The inline crop uses Teacher-specific learner wording,
explicit approval and authenticated hash-checked derivatives. The shared 1 MiB
photo limit is an explicit bounded opt-in; no original filename selects a file.
Camera cleanup deletes only resolved app-temporary captures, preserving gallery
originals and links outside the temporary directory.

## Verification

- The complete shared suite passed **591 tests** after the shared-photo merge.
  The final teaching cohort passed **17 repository/widget cases**, including
  lost generation/review/photo outcomes, background/late-response erasure,
  native-picker resume and revoked resume, unknown evidence, unknown script marks,
  explicit approval and visible allowance refusals.
- **14 Teacher entry/locale/camera/notice widget cases** passed. All offered
  language catalogues have the same keys; existing translations were retained.
  New English/Afrikaans/isiZulu/Arabic wording is draft school wording and still
  needs fluent review; other offered languages use explicit English fallbacks.
- Real Chrome passed the classic Teacher entry → uncertain generation recovery
  → line edit/save → uncertain approval recovery → private approval → revoked
  resume. The dedicated Facilitator build passed the same teaching workflow and
  both progress/discipline inbox journeys, with exact school/account headers,
  withdrawal erasure and a closed legacy auth box. Build selection used both
  --flavor=facilitator and --dart-define=FLAVOR=facilitator.
- Browser fixtures use synthetic typed API responses and no live provider. Real
  backend HTTP/domain, source/crop, idempotency and contract checks are recorded
  in teaching-assistant-backend.md; a further real HTTP refusal cohort passed
  **1 test / 34 assertions**, including three allowance causes and disabled AI.
- Whole-app PHPStan and affected source analysis passed without errors/warnings.
  CI runs the two teaching browser targets, and Android discovers the individual
  flows once. The combined browser entrypoint has a browser-only filename to
  avoid duplicate Android registration of its imported notice/teaching tests.

Actual camera/printer devices, live-model quality, cost/latency, provider/legal
qualification and independent safeguarding acceptance remain separate evidence.
Inference remains disabled. This work does not activate production or a store.
