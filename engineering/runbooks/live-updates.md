# Live service runtime

The existing PHP image supports `CONTAINER_ROLE=reverb` for the socket service
and `CONTAINER_ROLE=live-worker` for the dedicated database/live queue. Neither
role migrates, seeds, provisions a service or enables a school module. The
ordinary web service remains responsible for reviewed migrations. These are
build/runtime definitions; no service is deployed by this change.

`LIVE_UPDATES_ENABLED=false` is the default. Configure the same application key,
Reverb app ID/key/secret and domain database on web and live-worker; the Reverb
service uses the matching signed publisher credentials. Secrets have no source
default. The public websocket address uses `REVERB_PUBLIC_HOST/PORT/SCHEME`;
server-to-server publishing uses `REVERB_HOST/PORT/SCHEME`. Require WSS outside
local testing and configure the exact portal host allowlist. Client-originated
Reverb messages are disabled.

Web and live-worker must share the configured database/Redis cache and the same
server-side cookie session store. File/array leases are permitted only for local
single-host testing. Run the dedicated live worker continuously with allocated
CPU; minute scheduling is recovery only. The current Reverb configuration is a
single server with scaling disabled: keep one socket instance until a reviewed
shared scaling backend is configured and qualified. Websocket infrastructure
must support the chosen connection timeout/reconnect behavior.

Enable the school-scoped live_updates module separately. Source modules and
fresh current source authority still decide every event. A transport outage
leaves existing protected HTTP workflows available. Count-only presence expires
without heartbeat; no attendance or record access is inferred from presence.

Local qualification (isolated test database, Python 3 and normal PHP runtime):

```sh
LIVE_REVERB_INTEGRATION=true php vendor/bin/phpunit tests/Feature/Live/ReverbIntegrationTest.php
```

The test starts and stops actual local Reverb, a database worker and a small
standard-library websocket probe. It checks signed subscription, disabled
client messages and committed relay latency. CI executes this explicitly; the
ordinary test suite skips the process-dependent case unless opted in. A local
result is separate from deployment, load, mobile and production acceptance.
