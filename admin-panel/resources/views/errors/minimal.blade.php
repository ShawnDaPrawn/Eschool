<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        {{-- Same Material 3 tokens and typeface as the rest of the panel. --}}
        <link rel="stylesheet" href="{{ asset('/assets/css/m3-tokens.css') }}">
        <link rel="stylesheet" href="{{ asset('/assets/css/m3-base.css') }}">
        <style>
            *, *::before, *::after {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem 1rem;
                background: var(--md-sys-color-surface-container-low);
                color: var(--md-sys-color-on-surface);
                font-family: var(--md-ref-typeface-plain);
                -webkit-font-smoothing: antialiased;
            }

            .error-card {
                width: 100%;
                max-width: 480px;
                padding: 3rem 2rem 2.5rem;
                text-align: center;
                background: var(--md-sys-color-surface-container-lowest);
                border: 1px solid var(--md-sys-color-outline-variant);
                border-radius: var(--md-sys-shape-corner-extra-large);
            }

            .error-code {
                margin: 0 0 .5rem;
                font-size: var(--md-sys-typescale-display-large-size);
                line-height: var(--md-sys-typescale-display-large-line-height);
                font-weight: 700;
                color: var(--md-sys-color-primary);
            }

            .error-title {
                margin: 0 0 .75rem;
                font-size: var(--md-sys-typescale-headline-small-size);
                line-height: var(--md-sys-typescale-headline-small-line-height);
                font-weight: 600;
            }

            .error-message {
                margin: 0 0 .75rem;
                font-size: var(--md-sys-typescale-body-large-size);
                line-height: var(--md-sys-typescale-body-large-line-height);
                color: var(--md-sys-color-on-surface-variant);
            }

            .error-actions {
                margin-top: 1.5rem;
                display: flex;
                gap: .75rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            /* Material 3 filled and outlined buttons */
            .error-btn {
                display: inline-flex;
                align-items: center;
                height: 40px;
                padding: 0 1.5rem;
                border-radius: var(--md-sys-shape-corner-full);
                font-size: var(--md-sys-typescale-label-large-size);
                font-weight: 500;
                text-decoration: none;
                transition: background-color .15s ease, box-shadow .15s ease;
            }

            .error-btn--filled {
                background: var(--md-sys-color-primary);
                color: var(--md-sys-color-on-primary);
            }

            .error-btn--filled:hover {
                background: color-mix(in srgb, var(--md-sys-color-on-primary) var(--md-sys-state-hover-opacity), var(--md-sys-color-primary));
                box-shadow: var(--md-sys-elevation-level1);
            }

            .error-btn--outlined {
                border: 1px solid var(--md-sys-color-outline);
                color: var(--md-sys-color-primary);
            }

            .error-btn--outlined:hover {
                background: color-mix(in srgb, var(--md-sys-color-primary) var(--md-sys-state-hover-opacity), transparent);
            }

            .error-btn:focus-visible {
                outline: 2px solid var(--md-sys-color-primary);
                outline-offset: 2px;
            }
        </style>
    </head>
    <body>
        <main class="error-card">
            <p class="error-code">@yield('code')</p>
            <h1 class="error-title">@yield('message')</h1>
            @hasSection('description')
                <p class="error-message">@yield('description')</p>
            @endif
            <div class="error-actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a href="{{ url('/') }}" class="error-btn error-btn--filled">{{ __('Go Home') }}</a>
                @endif
            </div>
        </main>
    </body>
</html>
