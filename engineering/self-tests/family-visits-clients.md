# Meetings and early collection apps

The Parent app supports approved-current-child meeting booking/cancellation and
named-collector early collection requests. Collection remains pending explicit
staff approval; the account transport has no gate-reader collection operation.
Teacher and dedicated Facilitator profiles have their own host calendar, with
manager-only pass approvals/cancellation. The staff panel remains the source for
slot publication and school rules.

Every entry uses current module/account/school authority and a purpose-scoped
configuration probe. The dedicated shell does not open legacy teacher storage.
Sensitive records and editors disappear on background or context change. An
already submitted uncertain request retains an authenticated encrypted memory
copy for at most 15 minutes, with fresh source authorization before an explicit
retry of the same UUID/body. It never automatically resubmits. School time-zone
pickers reject invalid or ambiguous wall times. The Parent configuration no
longer returns the staff roster; available authorized slots still name their host.

Verified locally on 26 October 2026:

- All 13 shared repository/widget cases pass, including 390/1280-pixel layouts,
  wrong school/account/guardian/host data, substituted collector/window receipts,
  read-only controls, exact uncertain retries, fresh authorization and erasure.
- The affected native suite passes 21 tests / 212 assertions; selected Pint passes.
- Parent and staff selected Dart analysis have zero errors or warnings.
- Real Parent Chrome passes both Student denial and the Parent journey: book,
  cancel, submit a request whose first response is lost, explicitly recover the
  identical request, observe pending approval, cancel and erase on fresh refusal.
  There are four effects from five write attempts. Requests carry school/account
  scope. A learner receives neither an entry nor a private API call.
- All 20 offered app catalogues retain their existing values and provide all 42
  new labels, with explicit English fallbacks where draft translations are absent.

Pending before readying this client PR: Teacher and dedicated Facilitator Chrome
calendar journeys, both app entry/locale unit suites and the final CI result.
Those flows and the three explicit Chrome CI targets are included in source;
no unrun browser, native device, fluent-language or production pass is claimed.
