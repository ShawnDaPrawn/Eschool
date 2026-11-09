<?php

declare(strict_types=1);

/*
 * Google and Microsoft sign-in (Action Plan 5.9, part 2). OpenID Connect:
 * the panel uses the authorization code flow with PKCE, state and nonce; the
 * apps send the ID token their native Google or Microsoft sign-in returned,
 * with a nonce this server issued. Nothing here creates an account: a
 * provider account opens only a NavigateEd account it was linked to, at a
 * school that switched the provider on.
 * engineering/specs/platform/google-microsoft-sign-in.md.
 *
 * Client ids and secrets come from the environment (Secret Manager in
 * production) only. The values below are placeholders: with no client id a
 * provider is not offered anywhere, whatever a school chooses.
 */
return [

    // Clock skew allowed on exp, iat and nbf, in seconds.
    'leeway_seconds' => (int) env('SIGN_IN_PROVIDER_LEEWAY_SECONDS', 60),

    // An ID token issued longer ago than this is refused even before it expires.
    'maximum_token_age_seconds' => (int) env('SIGN_IN_PROVIDER_MAXIMUM_TOKEN_AGE_SECONDS', 600),

    // A state and nonce (web) or a nonce (apps) can be used once, within this many minutes.
    'challenge_minutes' => (int) env('SIGN_IN_PROVIDER_CHALLENGE_MINUTES', 10),

    // The providers' signing keys (JWKS): fetched over HTTPS, cached for a bounded time,
    // and refused (fail closed) when they cannot be fetched and no fresh copy is held.
    'jwks' => [
        // A copy is kept at least this long and at most this long, whatever Cache-Control says.
        'minimum_seconds' => 300,
        'maximum_seconds' => 21600,
        // An unknown key id fetches the set again, at most once in this many seconds.
        'refetch_cooldown_seconds' => 300,
        // A key set larger than this, or with more keys, is refused.
        'maximum_bytes' => 65536,
        'maximum_keys' => 32,
        'timeout_seconds' => 5,
    ],

    'providers' => [

        'google' => [
            'label' => 'Google',
            // The web (server) OAuth client: the panel's code flow, and the audience
            // of the apps' ID tokens when they ask for this server client id.
            'client_id' => env('GOOGLE_SIGN_IN_CLIENT_ID'),
            'client_secret' => env('GOOGLE_SIGN_IN_CLIENT_SECRET'),
            // Android and iOS client ids whose ID tokens the apps may also send, comma separated.
            'app_client_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('GOOGLE_SIGN_IN_APP_CLIENT_IDS', ''))))),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'jwks_url' => 'https://www.googleapis.com/oauth2/v3/certs',
            'issuers' => ['https://accounts.google.com', 'accounts.google.com'],
            'scopes' => 'openid email profile',
        ],

        'microsoft' => [
            'label' => 'Microsoft',
            // The Entra ID (multi-tenant, work and school accounts) app registration.
            'client_id' => env('MICROSOFT_SIGN_IN_CLIENT_ID'),
            'client_secret' => env('MICROSOFT_SIGN_IN_CLIENT_SECRET'),
            'app_client_ids' => array_values(array_filter(array_map('trim', explode(',', (string) env('MICROSOFT_SIGN_IN_APP_CLIENT_IDS', ''))))),
            // {tenant} is the school's one allowed tenant, or "organizations" when it allows several.
            'authorize_url' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/authorize',
            'token_url' => 'https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token',
            'jwks_url' => 'https://login.microsoftonline.com/common/discovery/v2.0/keys',
            // The issuer is always tenant specific: {tenant} is the token's own tid,
            // which must be one of the school's allowed tenants.
            'issuer_template' => 'https://login.microsoftonline.com/{tenant}/v2.0',
            'scopes' => 'openid email profile',
        ],

    ],

    // Email domains a school may not allow: anyone can make an account there.
    'consumer_domains' => ['gmail.com', 'googlemail.com', 'outlook.com', 'hotmail.com', 'live.com', 'msn.com', 'yahoo.com', 'icloud.com', 'me.com', 'proton.me', 'protonmail.com'],

];
