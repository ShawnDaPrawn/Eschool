<?php

use App\Services\Platform\Context\Contributors\ConfigurationDefaultsContributor;
use App\Services\Platform\Context\Contributors\CurrentSchoolContributor;
use App\Services\Platform\Context\Contributors\IdentityDefaultsContributor;
use App\Services\Platform\Context\Contributors\ProfileFieldsContributor;
use App\Services\Platform\Context\Contributors\SchoolsContributor;
use App\Services\Platform\Context\Contributors\UserContributor;

/*
|--------------------------------------------------------------------------
| GET /api/v2/me/context
|--------------------------------------------------------------------------
|
| The contributors that compose the answer, in order (see
| App\Services\Platform\Context\ContextContributor). Each work stream adds
| its own and never edits another's; every key and field stays as
| openapi.yaml (Context) describes it.
|
*/

return [

    'contributors' => [
        // Stream T1 (Tenancy): user, schools, current_school.
        UserContributor::class,
        SchoolsContributor::class,
        CurrentSchoolContributor::class,

        // Phase 0's answers for keys other streams own; each stream replaces
        // its line with its own contributors.
        IdentityDefaultsContributor::class,      // T2: sign_in_methods, children (approval)
        ConfigurationDefaultsContributor::class, // T3: modules, notification_rules, feature_flags
        ProfileFieldsContributor::class,         // T3: metadata only; private values are fetched separately.
    ],

];
