<?php

// Each private feature registers its own SQL source/owner predicate. A provider
// must never retrieve notice/private body columns or use cached app context.
// Existing unrelated types keep their current recipient rules.
return [
    'scopes' => [],
];
