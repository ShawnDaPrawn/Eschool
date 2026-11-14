@extends('layouts.master')
@section('title', __('School configuration'))
@section('content')
@php
    $sections = ['school'=>'School information','branding'=>'Branding','localization'=>'Regional preferences','links'=>'School links','policies'=>'Privacy and terms','security'=>'Sign-in protection'];
    if(auth()->user()->hasRole('Super Admin')) $sections['maintenance'] = 'Maintenance';
    $requestedSection = request('view','school');
    $section = is_string($requestedSection) && array_key_exists($requestedSection, $sections) ? $requestedSection : 'school';
    $sectionNav = [];
    foreach($sections as $key=>$label) $sectionNav[$key] = ['label'=>$label,'href'=>url('settings').'?view='.$key];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('School configuration')" :subtitle="__('Manage school details and preferences. Each section saves independently.')" />
    <x-panel-view-nav :items="$sectionNav" :current="$section" />
    @if($section === 'maintenance')
        <section class="m3-card m3-card--outlined panel-record-card">
            <h2>{{ __('Refresh application caches') }}</h2>
            <p>{{ __('Refresh shared application caches after configuration changes. Other active users may need to reload their page.') }}</p>
            <form method="POST" action="{{ url('clear') }}" data-year-safe data-confirm="{{ __('Refresh the shared application caches now? Other active users may need to reload their page.') }}">
                @csrf
                <button class="m3-button m3-button--outlined" type="submit">{{ __('Refresh caches') }}</button>
            </form>
        </section>
    @elseif($section === 'policies')
        <section class="m3-card m3-card--outlined panel-record-card"><h2>{{ __('school_privacy_and_terms') }}</h2><p>{{ __('school_privacy_and_terms_hint') }}</p>
            @foreach(['privacy_policy'=>'privacy_policy','terms_condition'=>'terms_condition'] as $type=>$label)
                <div class="js-school-policy" data-type="{{ $type }}" data-url="{{ url('setting-update') }}">
                    <label for="school_{{ $type }}">{{ __($label) }}</label><textarea id="school_{{ $type }}" class="js-rich-text">{{ $settings[$type] ?? '' }}</textarea>
                    <button type="button" class="m3-button m3-button--filled js-save-school-policy">{{ __('Save policy') }}</button>
                </div>
            @endforeach
        </section>
    @else
        <x-page.form-card :id="'school-settings-'.$section" :action="url('settings')" :title="__($sections[$section])" :submit-label="__('Save this section')" :draft="false">
            <input type="hidden" name="settings_section" value="{{ $section }}">
            <input type="hidden" name="expected_account_id" value="{{ auth()->id() }}"><input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
            @if($section === 'school')
                <div class="panel-form-grid">
                    <x-page.field name="school_name" :label="__('school_name')" :value="$settings['school_name'] ?? ''" required maxlength="255" />
                    <x-page.field name="school_email" type="email" :label="__('school_email')" :value="$settings['school_email'] ?? ''" required />
                    <x-page.field name="school_phone" type="tel" :label="__('school_phone')" :value="$settings['school_phone'] ?? ''" required />
                    <x-page.field name="school_tagline" type="textarea" :label="__('school_tagline')" :value="$settings['school_tagline'] ?? ''" required />
                </div>
                <x-page.field name="school_address" type="textarea" :label="__('school_address')" :value="$settings['school_address'] ?? ''" required />
            @elseif($section === 'branding')
                <div class="panel-form-grid">
                @foreach(['favicon'=>'favicon','logo1'=>'horizontal_logo','logo2'=>'vertical_logo','login_image'=>'login_image'] as $name=>$label)
                    <div><x-page.field :name="$name" type="file" :label="__($label)" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,image/x-icon" :help="__('Leave empty to keep the current image.')" />
                        @if(!empty($settings[$name]))<img src="{{ url(Storage::url($settings[$name])) }}" alt="{{ __($label) }}" width="160" loading="lazy">@endif
                    </div>
                @endforeach
                <x-page.field name="theme_color" type="color" :label="__('theme').' '.__('color')" :value="$settings['theme_color'] ?? '#1b4d3e'" required :help="__('certificate_colours_hint')" />
                <x-page.field name="secondary_color" type="color" :label="__('secondary').' '.__('color')" :value="$settings['secondary_color'] ?? '#2f6653'" required />
                </div>
            @elseif($section === 'localization')
                @php($zones = collect($getTimezoneList)->mapWithKeys(fn($zone)=>[$zone[2]=>$zone[2].' — GMT '.$zone[1].' — '.$zone[0]]))
                <div class="panel-form-grid">
                    <x-page.field name="time_zone" type="select" :label="__('time_zone')" :options="$zones" :value="$settings['time_zone'] ?? ''" required />
                    <x-page.field name="date_formate" type="select" :label="__('date_formate')" :options="$getDateFormat" :value="$settings['date_formate'] ?? ''" required />
                    <x-page.field name="time_formate" type="select" :label="__('time_formate')" :options="$getTimeFormat" :value="$settings['time_formate'] ?? ''" required />
                    <x-page.field name="online_payment" type="select" :label="__('online_payment_mode')" :options="['1'=>__('enable'),'0'=>__('disable')]" :value="isset($settings['online_payment']) && !$settings['online_payment'] ? '0' : '1'" required />
                </div>
                <section><h3>{{ __('Active school year') }}</h3><p>{{ $session_year->firstWhere('id', (int)($settings['session_year'] ?? 0))?->name }}</p>
                    @can('session-year-create')<a class="m3-button m3-button--outlined" href="{{ route('new-year.index') }}">{{ __('Prepare or activate the school year') }}</a>@endcan
                    <p>{{ __('School year activation is managed globally with a preview and carry-forward review.') }}</p>
                </section>
            @elseif($section === 'links')
                <div class="panel-form-grid">@foreach(['facebook'=>'facebook','instagram'=>'instagram','linkedin'=>'linkedin','maplink'=>'google_map_link'] as $name=>$label)
                    <x-page.field :name="$name" type="url" :label="__($label)" :value="$settings[$name] ?? ''" />
                @endforeach</div><p>{{ __('get_the_link_from_google_map_with_embed_url_and_paste_only_src_from_it') }}</p>
            @else
                <x-page.field name="recaptcha_status" type="select" :label="__('recaptcha').' '.__('status')" :options="['0'=>__('disable'),'1'=>__('enable')]" :value="$settings['recaptcha_status'] ?? '0'" />
                <x-page.field name="recaptcha_site_key" :label="__('site_key')" :value="$settings['recaptcha_site_key'] ?? ''" />
                @if(\App\Services\Platform\Secrets\SecretSettings::fromEnvironment('recaptcha_secret_key'))
                    <p>{{ __('set_in_environment') }}</p>
                @else
                    <x-page.field name="recaptcha_secret_key" type="password" :label="__('secret_key')" :restore-old-input="false" autocomplete="new-password" :help="__('Leave empty to keep the existing secret.')" />
                @endif
            @endif
        </x-page.form-card>
    @endif
</div>
@endsection
