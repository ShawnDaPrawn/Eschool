@extends('learner-setup.shell')

@section('content')
    <h1 class="h4 mb-2">{{ __('learner_setup_title') }}</h1>
    @if ($learner)
        <p class="text-muted">{{ __('learner_setup_for', ['name' => $learner->full_name, 'gr' => $learner->email]) }}</p>
    @else
        <p class="text-muted">{{ __('learner_setup_code_intro') }}</p>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $action }}" autocomplete="off">
        @csrf
        @if ($withCode)
            <div class="form-group">
                <label for="gr_number">{{ __('gr_number') }}</label>
                <input id="gr_number" name="gr_number" type="text" class="form-control form-control-lg" required
                    value="{{ old('gr_number') }}" autocapitalize="characters" autocomplete="username">
            </div>
            <div class="form-group">
                <label for="code">{{ __('learner_setup_code') }}</label>
                <input id="code" name="code" type="text" class="form-control form-control-lg" required
                    placeholder="ABCDE-FGHJK" autocapitalize="characters" spellcheck="false">
            </div>
        @endif
        <div class="form-group">
            <label for="password">{{ __('new_password') }}</label>
            <input id="password" name="password" type="password" class="form-control form-control-lg" required
                minlength="{{ \App\Services\Platform\Identity\PasswordPolicy::MIN_LENGTH }}" autocomplete="new-password"
                aria-describedby="password-hint">
            <small id="password-hint" class="form-text text-muted">{{ __('password_rules_hint') }}</small>
        </div>
        <div class="form-group">
            <label for="password_confirmation">{{ __('confirm_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password"
                class="form-control form-control-lg" required autocomplete="new-password">
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-block btn-theme btn-lg font-weight-medium auth-form-btn">
                {{ __('learner_setup_save') }}
            </button>
        </div>
    </form>
@endsection
