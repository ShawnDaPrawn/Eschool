# Support smoke picker navigation

Combined integration CI job 121182291485 on run 40197783256 failed while tapping a generic integer dropdown. The finder could observe the home school selector immediately after navigation began; once the Support route replaced it, the next tap had no matching widget. The actual Support learner field remained correctly implemented.

The smoke now waits for and taps the exact `learner-null` key through the existing hit-test-aware helper. Save, exact scope headers, current saved plan, withdrawn body erasure and unopened classic auth-box assertions remain unchanged. No product authorization, refusal or timing assertion is weakened and no fixed sleep is added.

Actual facilitator Chromium at 390px passes the full workflow on exact combined #179 source plus this locator change, exit 0. Local evidence: support-smoke-ci-chrome.log. This fixes the observed CI test race; remaining combined checks retain their independent outcomes.
