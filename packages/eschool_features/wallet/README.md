# eschool_wallet

Shared Material3 personal wallet index, private statements and guardian spending
controls. Used by family, teacher and dedicated facilitator clients. It requires
an active pinned school/account and wallet module, independently of fee access.

Money is validated as integer cents before generated DTO parsing. Mutations retain
one operation UUID for uncertain retries. Session boundaries clear private state
when school/account/token/roles/children/module access changes. Wallet creation
issues a zero-balance ledger account; funding and commerce are subsequent increments.
