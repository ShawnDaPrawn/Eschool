<?php

declare(strict_types=1);

/*
 * Answers Cloud Run's startup and liveness probes for the worker role, served
 * by `php -S` beside queue:work (docker/entrypoint.sh). The worker has no
 * public ingress; whether it keeps up with the queue is checked from outside
 * through the panel's GET /up/worker.
 */

header('Content-Type: text/plain');
header('Cache-Control: no-store');
echo "worker running\n";
