# Houses and awards — domain qualification

Implemented in the existing school-owned Laravel services, with both modules off
by default. The initial daily staff cap is 50 and remains editable; public point
moments and optional principal approval start off. The owner has not selected a
stricter mandatory award-approval stage.

Automated qualification: 22 MySQL feature tests, 97 assertions, pass. The configured
whole-application Larastan check reports no errors. Pint passes on the new models,
services, event, migration and tests. Tests use synthetic two-school fixtures and
perform no delivery to real recipients or external providers.

Coverage includes current school and physical grade placement, dated head,
homeroom and group authority, wrong role/school/guardian, pending password and
platform-operator refusal; exact UUID replay after authority changes; encrypted
point and nomination reasons; atomic daily absolute-issue budgets, school-local
midnight, append-only reversals without budget refunds, immutable provenance and
invalidation-only outbox events; rollback of ledger/budget/receipt/event together;
window/definition changes; subject/group eligibility; scoped subcategories;
optional principal approval; year-to-year definition reuse; retained award voids;
outside awards; permanent profiles containing presentations only; rollback refusal
when retained records exist.

The My grade adapter contract is `AwardNominations::catalogue(User,int,int)` and
`nominate(User,array)`; consumers do not write the award tables. The concrete
`HousePointsChanged` event contains only school/year/house/revision IDs. Live relay
and fresh-authority projections are separate integration work.

API contracts and SDK, the panel and assembly mode, app entries and celebration,
notification delivery guards, actual live latency and browser/device acceptance
are not established by these domain tests. This increment does not complete
Phase 4 or authorize production activation.
