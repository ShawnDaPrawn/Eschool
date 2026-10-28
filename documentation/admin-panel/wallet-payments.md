---
id: wallet-payments
title: Wallet QR and school-card payments
---

Enable **Wallet payments** for the school after configuring its wallets, spending
limits and active vendors. This is an online school-wallet payment. It requires
no bank-card gateway, and no balance is written to a school identity card.

In **Wallets → Shop and orders**, choose the original wallet, vendor and goods.
Review the exact amount, select **Pay at the counter by wallet QR**, then create
the QR. Creating it moves no money. Show it only to the intended counter. It
expires after two minutes; an unpaid instruction can also be cancelled. Use
**Wallet payments** to check the original outcome. A Parent can use an eligible
child's wallet only while their current guardian access and that child's limits
permit it. Learners cannot use an adult's wallet or request withdrawals.

The assigned operator opens **Wallet payments** in the Vendor panel or dedicated
Vendor app. Scan or paste the current wallet-payment QR, review the exact goods
and amount, then confirm once. The payment, stock movement and original order
receipt are recorded together. Hand over physical goods once after success.
Digital admissions are issued to the original wallet; their later admission
validation is a separate action.

For school-card tap, Finance registers a reader under **Money → Vendor payment
readers**, choosing one counter and an explicit validity of one to thirty days.
Finance needs vendor management and card-reader management permission. Give its
private pairing credential only to the assigned operator. Clear the pairing
page afterwards and revoke a lost or reassigned reader immediately. Revocation
remains available while card or payment writes are paused.

Pair the reader in the Vendor app for that session, select the goods, and tap
the encoded school identity card. Check its current private photo, the exact
items and amount before confirming. A frozen/replaced card, changed photo,
expired instruction, revoked reader or changed operator assignment needs a new
review. A learner can use an approved card before their first personal-app login.
Compatible mobile browsers can read NFC; other browsers can accept the school
card link from a paired reader. Actual camera/NFC availability depends on the
device. Backgrounding clears private reviews and reader pairing.

If confirmation is interrupted, check **original counter outcome** before taking
another payment or handing over anything. A recovered or repeated payment is
already recorded: use its original receipt and do not supply the goods again.
An unknown request is permanently abandoned before a new review, so its delayed
confirmation cannot debit later. The app retains only the scoped recovery ID,
never the reader secret, payment QR, card proof or photograph. The browser must
have session storage available before confirming a payment.

Pausing new payments retains original private receipts under current authority.
Payment controls never block ordinary learner academic access. Real NFC/camera,
staging and human acceptance are tracked separately from software verification.
