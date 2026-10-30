@extends('layouts.master')
@section('title', __('Role access by module'))
@section('content')
@php
    $role = $editor['role'];
    $levels = ['none' => __('No access'), 'view' => __('View'), 'contribute' => __('Contribute'), 'manage' => __('Manage')];
    $visible = collect($editor['modules'])->where('visible', true);
    // After a failed submission the form shows what was sent: an ability group
    // with nothing ticked is not sent at all, so it counts as empty, not as held.
    $submitted = session()->hasOldInput();
    $ticked = fn (string $module, string $ability, bool $held) => in_array($ability, $submitted ? (array) old('abilities.'.$module, []) : ($held ? [$ability] : []), true);
    $keepExtras = ! $submitted || filter_var(old('keep_extras', true), FILTER_VALIDATE_BOOLEAN);
    $sensitive = $visible->flatMap(fn ($module, $key) => collect($module['abilities'])->filter(fn ($a) => $a['sensitive'])->map(fn ($a, $ability) => $a + ['module' => $key, 'key' => $ability, 'module_name' => $module['name']]));
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$role ? __('Access for :role', ['role' => $role['name']]) : __('Create a role by module')"
        :subtitle="__('Choose what this role can do in each part of NavigateEd. Higher levels include the lower ones.')" />
    @if (! $role && isset($templates))
        <form method="GET" action="{{ route('role-access.create') }}" aria-label="{{ __('Start from a template') }}" class="page-section">
            <x-page.field name="template" id="role-template" type="select" :label="__('Start from a template')" :value="$editor['template']['key'] ?? null"
                :options="collect($templates)->map(fn ($t) => __($t['label']))->all()" :placeholder="__('Blank role')" :restore-old-input="false" />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Use template') }}</button>
        </form>
        @isset($editor['template'])
            <p role="status">{{ __('Started from :template. Sensitive abilities are never part of a template; tick them on purpose.', ['template' => __($editor['template']['label'])]) }}</p>
            <p>{{ __($templates[$editor['template']['key']]['description']) }}</p>
        @endisset
    @endif
    @if ($errors->any())
        <div role="alert"><ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ $role ? route('role-access.update', $role['id']) : route('role-access.store') }}" aria-label="{{ __('Role access by module') }}">
        @csrf
        @if ($role) @method('PUT') @endif
        <x-page.field name="name" id="role-name" :label="__('Role name')" :value="$role['name'] ?? (isset($editor['template']) ? __($editor['template']['label']) : null)" maxlength="125" required />

        <div class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
            <table class="table">
                <caption>{{ __('Access level in each module the school has switched on') }}</caption>
                <thead><tr><th scope="col">{{ __('Module') }}</th>@foreach ($levels as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach ($visible as $key => $module)
                    <tr>
                        <th scope="row">{{ __($module['name']) }}</th>
                        @foreach ($levels as $level => $label)
                            <td><label><input type="radio" name="levels[{{ $key }}]" value="{{ $level }}" @checked(old('levels.'.$key, $module['level']) === $level)>
                                <span class="sr-only">{{ __($module['name']) }}: {{ $label }}</span></label></td>
                        @endforeach
                    </tr>
                    @php $ordinary = collect($module['abilities'])->reject(fn ($a) => $a['sensitive']); @endphp
                    @if ($ordinary->isNotEmpty())
                        <tr><td colspan="5">
                            @foreach ($ordinary as $ability => $definition)
                                <label><input type="checkbox" name="abilities[{{ $key }}][]" value="{{ $ability }}" @checked($ticked($key, $ability, $definition['held']))> {{ __($definition['label']) }}</label>
                            @endforeach
                        </td></tr>
                    @endif
                @endforeach
                </tbody>
            </table>
        </div></div>

        @if ($sensitive->isNotEmpty())
            <div class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
                <h2>{{ __('Sensitive abilities') }}</h2>
                <p>{{ __('These open records about children’s health, wellbeing or identity, or approve money. Every grant is recorded in the audit log.') }}</p>
                @foreach ($sensitive as $ability)
                    <label><input type="checkbox" name="abilities[{{ $ability['module'] }}][]" value="{{ $ability['key'] }}" @checked($ticked($ability['module'], $ability['key'], $ability['held']))>
                        {{ __($ability['module_name']) }}: {{ __($ability['label']) }}</label>
                @endforeach
                <p><label><input type="checkbox" name="confirm_sensitive" value="1"> {{ __('I confirm this role may open the sensitive records I have ticked') }}</label></p>
            </div></div>
        @endif

        @if ($editor['extras'] !== [])
            <div class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
                <h2>{{ __('Individual permissions') }}</h2>
                <p>{{ __('This role also holds :count permissions from the earlier editor that no level or ability above explains.', ['count' => count($editor['extras'])]) }}</p>
                <ul>@foreach ($editor['extras'] as $permission)<li>{{ $permission }}</li>@endforeach</ul>
                <input type="hidden" name="keep_extras" value="0">
                <label><input type="checkbox" name="keep_extras" value="1" @checked($keepExtras)> {{ __('Keep them') }}</label>
            </div></div>
        @else
            <input type="hidden" name="keep_extras" value="1">
        @endif

        <button class="m3-button m3-button--filled" type="submit">{{ __('Save role') }}</button>
        <a class="m3-button m3-button--text" href="{{ route('roles.index') }}">{{ __('Back to roles') }}</a>
    </form>
</div>
@endsection
