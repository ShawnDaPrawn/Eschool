# Converter CI mirror correction

The previous combined run exhausted the converter job’s20minute limit while
Ubuntu’s Azure mirror downloaded ffmpeg dependencies; application/model tests had
not started. This is a package-download failure rather than a converter result.
The job now selects Ubuntu’s official HTTPS archive for Azure mirror entries and
sets bounded download timeouts/retries. Distribution signatures, pinned Python
hashes, model revision, converter tests and offline runtime checks are unchanged.

Actionlint and actual sed fixtures cover both Azure URL schemes and preserve
unrelated security/other mirrors. The new combined CI must run the full converter
checks; no timeout is reported as a passed conversion test.
