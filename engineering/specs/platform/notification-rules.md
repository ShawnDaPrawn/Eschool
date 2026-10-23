# School notification choices and delivery

School administrators manage the per-portal grid at Settings → Notification
rules. A rule is mandatory, on by default, off by default, or not offered.
The registry in `config/notification_types.php` sets offered portals, module,
channel defaults and platform locks. Account safety notices, urgent school
notices and guardian absence alerts cannot be weakened through school rows or
personal preferences. Future safeguarding types stay unavailable until their
module and permission exist.

Authenticated people manage their own global preferences through GET/PUT
`/api/v2/me/notification-preferences`. A choice follows the human across schools;
each school's mandatory/off rules still take precedence. Current school
memberships determine portals, including people with several roles. Schools
cannot alter each other's rules; callers cannot write another person's choice.
The shared Notifications settings screen decodes generated OpenAPI models.
Missing endpoints on older servers retain the existing unavailable state.

`Notifier` checks active school membership, module, effective rule and choice
for each channel. Business actions record recipient intent in the transactional
outbox; queued fanout checks current access and claims each notice/person/channel
once. Retries preserve successful channels. Push queue failures in asynchronous
mode propagate so a rolled-back claim can retry. Inbox opt-outs prevent new
recipient links, and new broadcasts require explicit links. Historical broadcast
rows remain readable without promoting old private assignment notices to portal
broadcasts. Legacy fee, assignment, leave, attendance and payment producers use
this service. Legacy chat sender and fee child metadata remain in push payloads.

Content goes to individual devices, rather than school topics that cannot
respect individual choices or ended memberships. School topics carry only the
existing silent `context.changed` invalidation. Push and mail jobs are encrypted
in the broker. Immediately before transport, rule-bound jobs recheck membership,
module, preference and destination; a changed mailbox/device or revoked access
records `suppressed` without contacting the provider. Unverified mailboxes get a
generic subject/body. An FCM UNREGISTERED refusal clears the exact stale token,
without clearing a newer replacement. Delivery failure logs exclude rendered
mail data. Critical invitation/password mail remains independent of optional
notice preferences.

Under-18 handle proposals record a dedicated outbox event. Only active,
verified, approving guardians receive the mandatory request, linking to
`/handles`. Pending/expiry and guardian rights are checked again at transport.
A source key and per-channel claims prevent duplicate notices on replay. Both
apps also recognise `/chat`, through the existing module guard; links still
switch to their authorised school before navigation.

Tables: scoped `notification_rules`, scoped `notification_dispatches`, global
`notification_preferences`. New notification delivery metadata is hidden from
v1 responses. Historical rows keep `preferences_enforced=false`; new rows set
it true. Migration rollback drops the dispatch references before notification
metadata. No production opt-out data is fabricated or removed.

Local and CI checks cover rules, wrong-school/role/owner, worker access changes,
queue encryption, retry deduplication, legacy visibility and payloads, contract
scenarios, migration rollback, both app routes and the panel grid. Real email,
FCM, phones, owner review and first-time-user timing remain staging acceptance
work, subject to the additional US$10/month ceiling.
