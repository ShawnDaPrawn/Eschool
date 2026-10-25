# Combined phase 3/4 CI corrections

The exact combined branch exposed cross-package integration failures that the
individual feature checks did not: two transitive websocket lock entries were
missing from the people and notification packages; the gallery smoke had an
unused import; four merged PHP configuration/route files needed the existing
formatter; and the two app route catalogues passed literal dynamic placeholders
to an integer-only consultation link parser.

The lockfiles now resolve the existing pinned websocket versions without
upgrading other dependencies. Both affected packages and the session package
pass enforced lockfile resolution. The route catalogue uses positive synthetic
IDs for parameter patterns; production parsing and refusal rules are unchanged.
Family route tests pass33, Teacher route tests22, full family analysis has no
errors/warnings, and the full PHP formatter sweep identified only the four
corrected files, whose follow-up formatter checks pass. No CI gate was removed.
Fresh CI must qualify the resulting combined tree before main.
