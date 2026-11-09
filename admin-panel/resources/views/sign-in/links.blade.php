@extends('layouts.master')

@section('title', __('Linked accounts'))

@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Linked accounts')" :subtitle="__('Sign in to this school with your school Google or Microsoft account instead of your password.')" />
    @if ($errors->any())
        <div role="alert">{{ $errors->first() }}</div>
    @endif
    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif

    <section class="page-section" aria-labelledby="links-yours">
        <h2 id="links-yours">{{ __('Your linked accounts') }}</h2>
        @if ($links === [])
            <p>{{ __('You have not linked an account at this school.') }}</p>
        @else
            <table class="page-table">
                <thead><tr><th scope="col">{{ __('Account') }}</th><th scope="col">{{ __('Last used') }}</th><th scope="col">{{ __('Remove') }}</th></tr></thead>
                <tbody>
                    @foreach ($links as $link)
                        <tr>
                            <td>{{ $link['label'] }}: {{ $link['email'] }}</td>
                            <td>{{ $link['last_used_at'] ?? __('Not yet') }}</td>
                            <td>
                                <form method="POST" action="{{ route('sign-in-links.destroy', ['link' => $link['id']]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="m3-button m3-button--text" type="submit">{{ __('Remove the link') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="page-section" aria-labelledby="links-new">
        <h2 id="links-new">{{ __('Link an account') }}</h2>
        @php($linkable = array_values(array_filter($offered, fn ($p) => $p['allowed'] && ! in_array($p['provider'], $linked, true))))
        @if ($linkable === [])
            <p>{{ __('There is nothing to link here right now. Your school chooses who may sign in with Google or Microsoft.') }}</p>
        @else
            <p>{{ __('Type your NavigateEd password to confirm it is you. You then sign in with the account you want to link. Its email address must be the one on your NavigateEd account.') }}</p>
            @foreach ($linkable as $provider)
                <form method="POST" action="{{ route('sign-in-links.start', ['provider' => $provider['provider']]) }}" aria-label="{{ __('Link a :provider account', ['provider' => $provider['label']]) }}">
                    @csrf
                    <x-page.field name="password" type="password" :id="'password-'.$provider['provider']" :restore-old-input="false" :label="__('Your NavigateEd password')" autocomplete="current-password" required maxlength="4096" />
                    <p><button class="m3-button m3-button--filled" type="submit">{{ __('Link a :provider account', ['provider' => $provider['label']]) }}</button></p>
                </form>
            @endforeach
        @endif
    </section>
</div>
@endsection
