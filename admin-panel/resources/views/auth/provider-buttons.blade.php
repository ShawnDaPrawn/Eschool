{{-- Google and Microsoft sign-in buttons (Action Plan 5.9): only for providers this school has switched on. --}}
@php($providerOptions = app(\App\Services\Platform\SignIn\ProviderSignIn::class)->loginOptions(request()->query('school')))
@if ($providerOptions['providers'] !== [])
    <div class="m3 provider-sign-in" aria-label="{{ __('Other ways to sign in') }}">
        <p>{{ __('Or sign in with your school account') }}</p>
        @foreach ($providerOptions['providers'] as $option)
            <form method="POST" action="{{ route('sign-in-provider.start', ['provider' => $option['provider']]) }}">
                @csrf
                <input type="hidden" name="school" value="{{ $providerOptions['school'] }}">
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Sign in with :provider', ['provider' => $option['label']]) }}</button>
            </form>
        @endforeach
    </div>
@endif
