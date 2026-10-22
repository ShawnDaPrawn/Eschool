<?php

/*
|--------------------------------------------------------------------------
| Tenancy
|--------------------------------------------------------------------------
|
| How requests find their school (App\Services\Platform\Tenancy\CurrentSchool;
| engineering/specs/platform/tenancy.md).
|
*/

return [

    /*
    | The school public pages, sign-in and webhooks work in until they know
    | better, and the school whose settings console work reads by default.
    | On a single-school install it is school 1, which every existing row
    | was back-filled to. It is also the school that may edit the platform's
    | own settings (mail server, push project, app versions) from the panel
    | until the platform admin area takes them over.
    */
    'default_school_id' => (int) env('TENANCY_DEFAULT_SCHOOL_ID', 1),

    /*
    | The header an app sends to choose among the signed-in user's schools.
    | A school the user has no active membership at is refused with 403.
    */
    'header' => 'X-School-Id',

    /*
    | The session key the panel's school switcher stores the choice under.
    */
    'session_key' => 'school_id',

];
