# Invitation mailbox and access binding

An invitation secret proves access only to the mailbox that received it.
Guardian account invitations and emailed learner password-setup links store a
hidden SHA-256 hash of that address at issue. Preview, acceptance, reminders and
queued delivery compare it with the current address. An old mailbox's secret
cannot verify a replacement email address, including after a bulk profile edit.
An ordinary email edit clears verification and retires outstanding links; changing
the address back does not reactivate them.

Learner setup also rechecks current school, active learner/guardian memberships
and guardian approval rights. Explicit ended/non-approving links override legacy
parent columns. Signed links and printed codes discover the school through their
secret, then validate and update there; the caller's earlier school context is
restored. Printed codes accept current learner login identifiers, including the
lifelong student number. Setting a password still grants no guardian consent.

Invitation mail carries its source invitation id in the encrypted job. Before
transport, the worker rechecks source validity, mailbox and relationship. Used,
expired, replaced or revoked sources suppress the job without contacting mail.
Legacy queued learner invitation bodies without source binding are suppressed;
legacy guardian mail without its source id is refused. Other critical mail keeps
its existing delivery path and cannot be disabled by optional notice choices.

Migration `2026_10_23_160000` adds the two hidden hashes. It retires outstanding
email links created before binding existed: today's address cannot prove where
the old link was delivered. Schools must reissue outstanding email invitations
after deployment. Printed setup codes remain valid until their existing expiry
or replacement. Migration rollback removes the new columns while keeping retired
credentials retired. This is code for a future authorised rollout, not a command
to alter production during development.
