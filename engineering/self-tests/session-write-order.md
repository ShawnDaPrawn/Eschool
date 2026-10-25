# Concurrent browser session writes

A real CI browser trace showed an AJAX table reader finish after a successful
school-year switch and save its older session over the new selection. The POST
sent year1 and completed at10:34:01.407; an older table request completed at.424;
the reload rendered year2 again. A successful response alone did not persist the
selection reliably when another browser request was already running.

Laravel now locks each session before reading it and releases the lock after
saving it. Local/install flows use file locks; installed Cloud Run instances use
the shared database lock store. SESSION_BLOCK_STORE can explicitly select another
supported shared atomic-lock store. Lock duration is120seconds, with a30second
wait. Different session IDs retain independent execution.

The regression starts two independent PHP processes through Laravel's actual
StartSession middleware. An old reader deliberately overlaps a newer year write.
Disabling blocking reproduced the old year overwriting the new one. Enabling it
passed with both file and database lock stores:2tests/6assertions. Whole-app static
analysis passes in the combined development tree. This source/test evidence does
not claim production rollout or a new cloud resource has been provisioned.
