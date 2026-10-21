<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Web System Update
    |--------------------------------------------------------------------------
    |
    | Lets a Super Admin upload an update zip that is extracted over the app's
    | code. On Cloud Run (which sets K_SERVICE) it is off by default: the
    | container is rebuilt from the repository for every release, and an
    | upload that replaces code would let anyone holding a stolen admin
    | session run their own code on the server.
    |
    */

    'enabled' => (bool) env('SYSTEM_UPDATE_ENABLED', ! env('K_SERVICE')),

];
