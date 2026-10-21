{{--
    School-wide notices for the people who can act on them, under the header:
    the current school year has ended, or the email settings were never
    verified. (They used to sit inside the header, where they overlapped it at
    1024 px.)
--}}
@php
    $panelYear = $panelYear ?? app(\App\Services\Panel\SessionYearContext::class);
    $currentYear = $panelYear->current();
    $currentYearEnded = $currentYear && $currentYear->end_date < date('Y-m-d');
    $emailVerified = (string) (DB::table('settings')->where('type', 'email_configration_verification')->value('message') ?? '0') !== '0';
@endphp
@if ($currentYearEnded && auth()->user()->can('session-year-create'))
    <div class="panel-notice" role="status">
        <x-icon name="alert" :size="18" class="panel-notice-icon" />
        <p class="panel-notice-text">
            {{ __('current_session_year_has_ended', ['name' => $currentYear->name, 'date' => date(getSettings('date_formate')['date_formate'] ?? 'd-m-Y', strtotime($currentYear->end_date))]) }}
            <a href="{{ Route::has('new-year.index') ? route('new-year.index') : url('session-years') }}">{{ __('create_the_next_session_year') }}</a>
        </p>
    </div>
@elseif (! $emailVerified && auth()->user()->can('email-setting-create'))
    <div class="panel-notice" role="status">
        <x-icon name="alert" :size="18" class="panel-notice-icon" />
        <p class="panel-notice-text">
            {{ __('email_configration_is_not_verified') }}
            <a href="{{ route('setting.email-config-index') }}">{{ __('click_here_to_redirect_to_email_configration') }}</a>
        </p>
    </div>
@endif
