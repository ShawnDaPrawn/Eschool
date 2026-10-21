<!-- partial:../../partials/_navbar.html -->
@if (Session::get('locale'))
    {{ app()->setLocale(Session::get('locale')) }}
@endif

<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <a class="navbar-brand header-logo brand-logo" href="{{ URL::to('/home') }}"> <img
                src="{{ schoolLogoUrl('logo1') }}" alt="logo"> </a>
        <a class="navbar-brand brand-logo-mini" href="{{ URL::to('/') }}"> <img
                src="{{ schoolSetting('favicon') ? asset('storage/' . schoolSetting('favicon')) : asset('assets/logo.svg') }}" alt="logo"> </a>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-stretch">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize"
            aria-label="{{ __('toggle') . ' ' . __('sidebar') }}">
            <x-icon name="menu" :size="22" />
        </button>
        @php
            $email_config_verify_value = DB::table('settings')
                ->select('message')
                ->where('type', 'email_configration_verification')
                ->first();
             

           
            if ($email_config_verify_value) {
                $message = $email_config_verify_value->message;
            } else {
                $message = 0;
            }
        @endphp
        @php
            $current_year_row = DB::table('session_years')
                ->select('name', 'end_date')
                ->where('id', getSettings('session_year')['session_year'] ?? null)
                ->whereNull('deleted_at')
                ->first();
            $current_year_ended = $current_year_row && $current_year_row->end_date < date('Y-m-d');
        @endphp
        @if ($current_year_ended && auth()->user()->can('session-year-create'))
            <div class="mx-auto order-0 d-none d-lg-block">
                <div class="alert alert-fill-danger my-2" role="alert">
                    <x-icon name="alert" :size="16" />
                    {{ __('current_session_year_has_ended', ['name' => $current_year_row->name, 'date' => date(getSettings('date_formate')['date_formate'] ?? 'd-m-Y', strtotime($current_year_row->end_date))]) }}
                    <a href="{{ url('session-years') }}" class="alert-link">{{ __('create_the_next_session_year') }}</a>.
                </div>
            </div>
        @elseif ($message == 0)
            @can('email-setting-create')
                <div class="mx-auto order-0 d-none d-lg-block">
                    <div class="alert alert-fill-danger my-2" role="alert">
                        <x-icon name="alert" :size="16" /> {{ __('email_configration_is_not_verified') }} <a
                            href="{{ route('setting.email-config-index') }}" class="alert-link">{{ __('click_here_to_redirect_to_email_configration') }}</a>.
                    </div>
                </div>
            @endcan
        @endif
        @php
             $current_session_id = getSettings('session_year')['session_year'];
            
            $current_session = DB::table('session_years')
                ->select('id', 'name')
                ->where('id', $current_session_id)
                ->whereNull('deleted_at')
                ->first();
            $session_years = DB::table('session_years')
                ->select('id', 'name', 'start_date', 'end_date')
                ->whereNull('deleted_at')
                ->orderBy('id', 'ASC')
                ->get();
           
           

            $current_session = $session_years->firstWhere('id', $current_session_id);
            $filter_session_year_selected = session('session_year', $current_session_id);
            
        @endphp
        <ul class="navbar-nav navbar-nav-right d-none d-lg-flex">
            <li>
                <div class="d-flex align-items-center gap-between-elements-1">
                    <span class="info-tooltip">
                        <x-icon name="info" :size="18" />
                        <span class="tooltip-text">
                            {{ __('sessionYearFilterInfo') }}
                        </span>
                    </span>
                   

                    <select id="filter_session_year_global" data-scope="global"
                        class="session-year-dropdown filter_session_year">
                        @foreach ($session_years as $session_year)
                            <option value="{{ $session_year->id }}"
                                data-start-date="{{ date('d-m-Y', strtotime($session_year->start_date)) }}"
                                data-end-date="{{ date('d-m-Y', strtotime($session_year->end_date)) }}"
                                {{ $session_year->id == $filter_session_year_selected ? 'selected' : '' }}>
                                {{ $session_year->name }}
                                @if ($current_session && $session_year->id == $current_session->id)
                                    - {{ __('system_default') }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </li>

            @if (Auth::user()->hasRole('Super Admin'))
                <li class="nav-item d-flex align-items-center">
                    <form action="{{ url('clear') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="topbar-action-btn">
                            <x-icon name="refresh" :size="16" />
                            <span>{{ __('cache_clear') }}</span>
                        </button>
                    </form>
                </li>
            @endif
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator dropdown-toggle" id="messageDropdown" href="#"
                    data-toggle="dropdown" aria-expanded="false">
                    <x-icon name="language" :size="20" />
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown language-dropdown"
                    aria-labelledby="messageDropdown">
                    @foreach (get_language() as $key => $language)
                        <a class="dropdown-item language-item {{ app()->getLocale() == $language->code ? 'active' : '' }}"
                            href="{{ url('set-language') . '/' . $language->code }}">
                            <span class="language-name">{{ $language->name }}</span>
                            @if (app()->getLocale() == $language->code)
                                <x-icon name="check" :size="16" class="language-check" />
                            @endif
                        </a>
                    @endforeach
                </div>
            </li>
            @include('layouts.partials.profile-dropdown', ['id' => 'profileDropdownDesktop'])
        </ul>

        <ul class="navbar-nav navbar-nav-right d-flex d-md-none">
            @include('layouts.partials.profile-dropdown', ['id' => 'profileDropdownMobile'])
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
            data-toggle="offcanvas" aria-label="{{ __('menu') }}">
            <x-icon name="menu" :size="22" />
        </button>
    </div>
</nav>
