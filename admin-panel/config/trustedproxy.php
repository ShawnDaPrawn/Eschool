<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Proxies whose X-Forwarded-For/-Proto/-Host headers are believed: '*' trusts
    | whatever connects to the app, or give a comma-separated list of addresses or
    | CIDR ranges. Trusting a visitor's own connection would let them pick their IP
    | address with a header and get around the per-address rate limits, so nothing
    | is trusted by default. On Cloud Run (which sets K_SERVICE) only Google's front
    | end can reach the container, so the calling address is trusted there.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', env('K_SERVICE') ? '*' : null),

];
