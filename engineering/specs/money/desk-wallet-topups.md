# Desk wallet top-ups and independent approval

Increment 8B moves verified school-owned master funds into a personal wallet. The
school explicitly sets an approval threshold in its wallet currency. Amounts above
that threshold need a different currently authorized desk operator; an equal amount
can credit immediately. Zero requires independent approval for every positive grant.
A reserved grant retains its original threshold and also obeys a stronger current
rule. Changing policy cannot remove an existing second-approval requirement.

Reservation debits the master and credits a dedicated nonnegative liability. Release
moves that reservation to the named personal wallet; cancellation returns a pending
school-owned grant to the master. Each root and its one terminal action are immutable,
with exact balanced cents postings, encrypted reasons and permanent actor-bound
operation receipts. Competing grants share the school lock and cannot overdraw the
master. Nightly reconciliation verifies roots, terminal entries and pending balances;
unattributed entries or domain failures block projection repair. Retained records
refuse destructive migration rollback.

`wallet-desk-process` permits recording, independent approval, cancellation and desk
receipts without bank funding or policy access. `wallet-desk-policy-manage` together
with `wallet-finance-manage` permits threshold changes. Fresh school membership,
roles, permissions, module status and recipient eligibility apply before and after
writes and before replay. Learners require canonical verified guardian approval.
An inactive recipient can have a pending school-owned grant cancelled, not released.
A requester who loses desk authority cannot have their pending grant approved.

The Money navigation includes Desk wallet top-ups and private printable receipts.
The v2 wallet desk-topups read endpoint and shared family, teacher and dedicated
facilitator wallet screens show private receipts with pending, credited or cancelled
status. Parents need current approving guardian authority, independent of fee access.
No private operator reasons, master accounts or funding evidence enter personal APIs.
Wallet/owner pairing, raw integer cents and response scope are checked before display.
Receipt refresh reloads the statement and clears private data when authority changes.

School-owned desk grants do not implement parent deposits, parent-to-child transfers
or child returns. Those remain increment 8C. Gateway integration remains deferred.
