<?php

/*
|--------------------------------------------------------------------------
| Admin panel behaviour
|--------------------------------------------------------------------------
|
| Settings for the admin panel's shell: what stays writable while the panel
| shows a school year other than the current one. The navigation itself is
| in config/navigation.php.
|
*/

return [

    'read_only_years' => [
        /*
         * While the header's year switcher shows a year other than the
         * school's current one, the EnforceReadOnlyYears middleware refuses
         * every POST, PUT, PATCH and DELETE in the panel except these
         * ($request->is() patterns). Deny by default: anything not listed is
         * treated as year-scoped data. Add a pattern only for a write that
         * does not depend on the school year.
         */
        'allow' => [
            // Switching the year or the school, and signing out.
            // Chip personalization targets a fresh current identity, not the selected academic year.
            'school-cards/cards/*/encoding',
            'set-session-year',
            'school/switch',
            'logout',

            // The signed-in person's own profile and password.
            'update-profile',
            'changePassword',
            'checkPassword',

            // School years are managed from any year: creating, editing and
            // making a year current, and the new-year wizard.
            'session-years',
            'session-years/*',
            'remove-installment-data/*',
            'new-year',
            'new-year/*',

            // Plan configuration always names its target year and has no invoice/
            // money effects. Preparing a new year must work from any header year.
            'fee-plans/*',
            'fee-accounts/*',
            // Receipt/allocation writes settle retained invoices without editing year records.
            'fee-reminders', 'fee-reminders/*', 'fee-reporting/*', 'bank-statements/*', 'fee-payments/*', 'fee-credit-notes/*', 'fee-discounts/*', 'fee-lifecycle', 'fee-lifecycle/*', 'fee-leavers', 'fee-leavers/*', 'fee-refunds', 'fee-refunds/*', 'wallet-settings', 'wallet-settings/*', 'wallet-funding', 'wallet-funding/*', 'wallet-topups', 'wallet-topups/*', 'wallet-deposits', 'wallet-deposits/*', 'fee-items/*', 'fee-item-charges/*', 'fee-item-batches/*',
            'fee-bill-runs/new',
            'fee-bill-runs/*/approve',

            // Reports target an explicit year/batch. Fresh canonical authority,
            // approval order and revision checks govern historical corrections.
            'academic-reports/*',
            // Dated learner changes carry an explicit school year and preview fingerprint.
            'academic-transitions/*',
            'academic-sasams/*',
            'academic-content',
            'academic-content/*',

            // Settings that are the same in every year.
            'clear',
            'settings',
            'app-settings',
            'setting-update',
            'email-settings',
            'verify-email-settings',
            'notification-setting',
            'chat-settings-update',
            'delete-chat-messages',
            'id-card-settings/update',
            'remove-image/*',
            'fees-config/update',
            'language',
            'language/*',
            'email/store',
            'form-fields',
            'form-fields/*',
            'roles',
            'roles/*',
            'online-exam/store-terms-conditions',

            // The public website's content and the app banners.
            'content/*',
            'content-bulk-update',
            'educational-program/*',
            'faq/*',
            'contact-us/*',
            'sliders',
            'sliders/*',

            // POST requests that only read or print: result lists, reports,
            // exports, certificates and ID cards.
            'exams/fetch-exam-result-list',
            'student-result-details',
            'student/export',
            'bonafide-certificate',
            'leaving-certificate',
            'generate-id-card',
            'generate-staff-id-card',
        ],
    ],

];
