# Event tickets through the wallet storefront — increment14

Schools explicitly configure an event's sale/admission windows, general or named
product-backed ticket types, capacity and transfer rule. Ticketing starts disabled.
Checkout uses the existing wallet/vendor quote, cents/VAT, daily/category limits,
guardian checks and school lock. Ticket-only carts issue digital admission tickets
atomically and record original digital fulfilment for merchant settlement. Mixed
physical/digital carts require separate checkouts; no physical goods are silently
marked collected. Products attached to ticket types must be new and uncounted;
ordinary stock editing cannot change admission capacity.

Each issued ticket retains its original wallet order line/ordinal, event/type/price
snapshot, holder and named learner. A named admission keeps that original learner
through every transfer; a receiving parent must currently be that learner's verified
guardian. Current holder/guardian checks precede every private ticket/QR read and
cached response. General transfers follow the school's explicit none/families/
any-current-wallet-profile rule. The recipient supplies a short-lived opaque
school-bound receiving code, rather than exposing a user directory.

Transfers retain immutable ownership actions and rotate both QR and typed admission
credentials. Old owners and old credentials cannot redeem a transferred ticket.
Counter lookup/redemption is bound to the original ticket vendor and fresh Vendor
operator assignment, or authorised Finance. Redemption within the configured event
window is single-use, concurrent-safe and replay-safe. Receipts retain original
redemption time and actor attribution. No money moves during admission or transfer.
Paused modules retain private history and refuse new purchases/transfers/redemptions.

Finance manages events/types and reviews capacity and original sales. Learner,
Parent, Teacher and Facilitator wallets show held tickets; guardians see only
currently authorised children. The Vendor panel/scanner supports ticket QR and typed
credentials and retains an original outcome after an interrupted request. Contract,
SDK, applicable app/counter views, retained migration/history and concurrency
verification form part of this increment. No bank gateway or Phase6 collection.
