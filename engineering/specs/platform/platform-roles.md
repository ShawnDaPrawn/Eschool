# Platform staff roles

Action Plan item SA.1. Until now everyone with an HQ admin grant could do
everything in the platform area, and grants were given only from the operator
console. Now each grant carries a role, every HQ page and action checks it,
and Owners invite staff by email from HQ.

## Roles

`config/platform_roles.php`. One role per grant (`platform_admin_grants.role`).

| Role | Can |
| --- | --- |
| Owner | everything, including managing platform staff |
| Operations | see schools; set them up; change package, status and modules; customer records; packages; audit log; LURITS claims; incident register; AI control centre (view); approved support sessions |
| Support | see schools; audit log; LURITS claims; incident register; AI control centre (view); approved support sessions |
| Finance | see schools; change package; customer records; packages; audit log and its export |
| Content | see schools; packages |
| Sales (read-only) | see schools; packages |

Abilities: `schools.view`, `schools.create`, `schools.package`,
`schools.status`, `schools.modules`, `accounts.manage`, `packages.view`,
`audit.view`, `audit.export`, `identity.review`, `incidents.manage`,
`support.access`, `support.emergency` (Owner only; see `support-access.md`), `ai.view`, `ai.manage` and `ai.caps` (both Owner only; see `hq-ai.md`), `staff.manage`. A role that is no longer in the
config grants nothing. Existing grants became Owner, so nobody lost access.

Every `/saas` route except the operator's own password names its ability with
the `platform.can:{ability}` middleware (`EnsurePlatformAbility`, after
`platform.staff`); `SaasPanelTest` fails the build for a route without one.
Pages hide what the role cannot do: the set-up link, the package, status and
module controls, and the staff link.

## Platform staff page

`/saas/staff` ("Platform staff" in the platform bar, Owners only) lists every
platform staff identity with their role, until when they have access, and
whether they have accepted their invitation. An Owner can:

- **invite** someone by email with a role, a number of days (1 to 365) and a
  reason. This creates an account with no school membership (an address that
  already belongs to a school account is refused), the platform staff identity
  and the grant, and emails a link (`PlatformStaffInvitationMail`);
- **change** a person's role and expiry, with a reason;
- **revoke** access, with a reason. This also voids any unused link;
- **send a new invitation** to someone who has not chosen a password yet. The
  new link replaces the old one.

The install always keeps at least one active Owner: demoting or revoking the
last one is refused. An Owner whose user account is disabled or deleted does
not count, since that account cannot sign in. Each change is audited (`platform_staff_role_granted`,
`platform_staff_revoked`, `platform_staff_invited`,
`platform_staff_invitation_accepted`) with its reason, and shows in the HQ
audit log.

## Invitations

`platform_staff_invitations` (platform table) keeps the SHA-256 of a 64-character
token and of the address it was sent to. A link lasts three days and works
once. It works only while the person is active platform staff with a current
grant, has no school membership, and still has the address it was sent to.
`/platform/invite/{token}` (no sign-in, private, never cached) sets the chosen
password (the password policy applies), verifies the mailbox, and ends every
other session and app sign-in. The person then signs in and lands in `/saas`.
A queued email re-checks all of this before it is sent.

The operator console still works: `platform:admin-access {user} --hours=…`
now takes `--role=` (unchanged when left out, Owner for a new grant).

## Tests

`Platform/PlatformRolesTest`:
- each role reaches only its pages and actions, and a retired role grants nothing;
- an Owner invites someone, who chooses their own password, signs in to `/saas` and cannot manage staff;
- a queued email for a used link is not sent;
- school accounts are refused, and revoking access voids the link;
- the last Owner cannot be demoted or revoked, and a disabled or deleted Owner does not count;
- the staff page forms validate and save.

`SaasPanelTest` requires an ability on every `/saas` route. `saas-panel.spec.ts`
opens the staff page at 390, 820 and 1280 px with axe and an overflow check.

## Not yet

Time-limited elevation (asking for a higher role for an hour) and two-person
approval for the Owner role.
