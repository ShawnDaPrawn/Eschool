# Exact synthetic card fixture fingerprints

Gitleaks 8.30.1 flagged SignedCardQrTest line 15 in the initial card-domain commit.
That literal is a generated UUID identifying a signing-key database record. It
is not a signing secret: the test creates its Ed25519 pair randomly. RFC 8032
section 7.1 known answers appear separately in the same file; the reported UUID
is not the RFC private seed.

The typed SDK fixture also has a deliberately unsigned NEQ1.synthetic.signed
shape example. It cannot pass the actual proof parser/signature verifier.

Only those exact public commit/path/rule/line fingerprints are added to the
existing .gitleaksignore. No rule, regex or path-wide exclusion changes. Scanning
the complete public history reachable from card API head 3357bda with 8.30.1
passes: 543 commits, approximately 48.33 MB. An unrelated synthetic api_key
negative control still triggers the unchanged generic-api-key rule.
