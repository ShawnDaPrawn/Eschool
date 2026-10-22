@extends('learner-setup.shell')

@section('content')
    <h1 class="m3-headline-small">{{ __('learner_setup_title') }}</h1>
    @if ($learner)
        <p class="m3-body-medium m3-text-muted">{{ __('learner_setup_for', ['name' => $learner->full_name, 'gr' => $learner->email]) }}</p>
    @else
        <p class="m3-body-medium m3-text-muted">{{ __('learner_setup_code_intro') }}</p>
    @endif

    @if ($errors->any())
        <div class="m3-banner m3-banner--danger" role="alert">
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" autocomplete="off" class="m3-stack m3-gap-4">
        @csrf
        @if ($withCode)
            <label class="m3-text-field">
                <input id="gr_number" name="gr_number" type="text" class="m3-text-field__input" placeholder=" " required
                    value="{{ old('gr_number') }}" autocapitalize="characters" autocomplete="username">
                <span class="m3-text-field__label">{{ __('gr_number') }}</span>
            </label>
            <label class="m3-text-field">
                <input id="code" name="code" type="text" class="m3-text-field__input" placeholder=" " required
                    autocapitalize="characters" spellcheck="false">
                <span class="m3-text-field__label">{{ __('learner_setup_code') }}</span>
            </label>
        @endif
        <div>
            <label class="m3-text-field">
                <input id="password" name="password" type="password" class="m3-text-field__input" placeholder=" " required
                    minlength="{{ \App\Services\Platform\Identity\PasswordPolicy::MIN_LENGTH }}" autocomplete="new-password"
                    aria-describedby="password-hint">
                <span class="m3-text-field__label">{{ __('new_password') }}</span>
            </label>
            <p id="password-hint" class="m3-text-field__supporting">{{ __('password_rules_hint') }}</p>
        </div>
        <label class="m3-text-field">
            <input id="password_confirmation" name="password_confirmation" type="password" class="m3-text-field__input"
                placeholder=" " required autocomplete="new-password">
            <span class="m3-text-field__label">{{ __('confirm_password') }}</span>
        </label>
        <button type="submit" class="m3-button m3-button--filled m3-button--block">{{ __('learner_setup_save') }}</button>
    </form>
@endsection
