@extends('saas.layout')

@section('title', __('Set up a school'))

@section('content')
<h1>{{ __('Set up a school') }}</h1>
<p class="saas-lead">{{ __('Creates the school with its roles and profile defaults, and its first administrator. The administrator gets a one-time password shown once on the next page; nothing is emailed. They must change it at first sign-in.') }}</p>
<form method="POST" action="{{ route('saas.schools.store') }}" class="saas-card">
    @csrf
    <h2>{{ __('The school') }}</h2>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="name">{{ __('School name') }}</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="off">
        </div>
        <div class="saas-field">
            <label for="slug">{{ __('Short name for addresses') }}</label>
            <input id="slug" name="slug" value="{{ old('slug') }}" maxlength="64" pattern="[a-z0-9]+(-[a-z0-9]+)*" aria-describedby="slug-help">
            <small id="slug-help">{{ __('Lower-case letters, numbers and hyphens. Leave blank to make one from the name.') }}</small>
        </div>
        <div class="saas-field">
            <label for="timezone">{{ __('Time zone') }}</label>
            <select id="timezone" name="timezone" required>
                @foreach ($timezones as $zone)
                    <option value="{{ $zone }}" @selected(old('timezone', $defaultTimezone) === $zone)>{{ $zone }}</option>
                @endforeach
            </select>
        </div>
        <div class="saas-field">
            <label for="package_key">{{ __('Package') }}</label>
            <select id="package_key" name="package_key" required>
                @foreach ($packages as $package)
                    <option value="{{ $package }}" @selected(old('package_key', 'core') === $package)>{{ $package }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <h2>{{ __('The first administrator') }}</h2>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="admin_first_name">{{ __('First name') }}</label>
            <input id="admin_first_name" name="admin_first_name" value="{{ old('admin_first_name') }}" required maxlength="100" autocomplete="off">
        </div>
        <div class="saas-field">
            <label for="admin_last_name">{{ __('Last name') }}</label>
            <input id="admin_last_name" name="admin_last_name" value="{{ old('admin_last_name') }}" required maxlength="100" autocomplete="off">
        </div>
        <div class="saas-field">
            <label for="admin_email">{{ __('Email (their sign-in)') }}</label>
            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required maxlength="255" autocomplete="off" aria-describedby="email-help">
            <small id="email-help">{{ __('Must not have an account yet.') }}</small>
        </div>
        <div class="saas-field">
            <label for="admin_mobile">{{ __('Mobile number (optional)') }}</label>
            <input id="admin_mobile" name="admin_mobile" value="{{ old('admin_mobile') }}" maxlength="30" autocomplete="off">
        </div>
    </div>
    <div class="saas-actions">
        <button class="saas-btn" type="submit">{{ __('Create school') }}</button>
        <a class="saas-btn saas-btn--quiet" href="{{ route('saas.index') }}">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
