# Web preview review

The preview workflow retains both app builds, preview labels, downloadable
artifacts, manual preview channels and server reachability checks. Pull requests
can build without Firebase credentials. Publishing requires an explicit
`deploy_preview` dispatch from `main` and defaults to staging; the configured
production endpoint is refused. The source-commit artifact records the actual
checked-out tree commit. The CORS preflight includes the app’s school/account
headers. No Firebase site, channel, API configuration or production data was
changed during this review.

Verification on 25 October 2026: actionlint and Bash syntax for every run step
pass. Executed server selection against default staging, explicit isolated URL,
repository-variable URL, configured production refusal, HTTP refusal, URL-path
refusal and command-substitution refusal. Verified that all credential/site/
deploy/server-check/summary steps require the explicit main-branch dispatch.
Existing app code is unchanged by this workflow correction. Actual Firebase
publication remains unrun; workflow summaries must not imply it passed.
