# School assistance API and generated client

The private API has 21 operations / 19 new paths / 39 schemas, plus optional,
no-default `FacilitatorFeatures.school_assistance`. All 430 preceding paths and
1,103 schemas remain structurally identical except that additive capability.
The Parent and explicitly authorised school-office surfaces are separate, with
fresh school/account/guardian/claim checks and private refusal headers. The API
covers approved documents, exact-UUID threads/replies/claim/reassignment, review
turns, disabled inference and bounded source-message translations. Screens and
workbench remain the next increment, not claimed by this API publication.

Evidence:

- Typed positive/refusal HTTP: 4 / 141 passed initially; combined current typed
  HTTP and source-domain cohort then passed 17 / 224. The final effective school
  cap case passed 1 / 16, proving HTTP429 and no provider dispatch.
- The full role/tenant/API-contract run executed 1,268 cases: 1,267 passed. Its
  sole failed new scenario assumed a school could disable the always-on academics
  module. Reading ModuleGate proved this was an invalid product state; that
  speculative scenario and redundant branch were removed. Real guardian,
  ownership, role, module and source-revision refusals remain covered; the affected
  17-case cohort passed after correction. No guard assertions or global checks
  were exempted.
- SDK: 2,354 generated Dart files; all 2,355 source/FILES entries byte-identical
  across two pinned7.25.0 generations. Existing156 tests and five new protocol
  tests pass. Analysis has zero errors/warnings (1,280 generated style infos).
- Strict Redocly2.57 lint, selected affected PHPStan and Pint pass. Only the final
  cap-code string mapping and its passing HTTP test followed that static run.

No real provider call, semantic translation/evaluation qualification, physical
mobile-device or production acceptance is claimed. Inference/provider defaults
remain disabled. The new unavailable envelope carries bounded reasons without
pretending that an answer exists; school/actor/learner allowance caps return429.
