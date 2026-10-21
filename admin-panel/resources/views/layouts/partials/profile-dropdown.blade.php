{{--
    Account menu in the panel header (avatar, name, profile, password, sign out).
--}}
@props(['id' => 'profileDropdown'])
<li class="nav-item nav-profile dropdown">
    <button type="button" class="nav-link dropdown-toggle" id="{{ $id }}" data-toggle="dropdown" aria-haspopup="true"
        aria-expanded="false" aria-label="{{ __('nav_account_menu', ['name' => Auth::user()->first_name]) }}">
        <span class="nav-profile-img">
            <img src="{{ Auth::user()->image }}" alt="" onerror="onErrorAvatar(event)">
        </span>
        <span class="nav-profile-text d-none d-xl-inline">
            <span class="mb-0 text-black">{{ Auth::user()->first_name }}</span>
        </span>
    </button>
    <div class="dropdown-menu dropdown-menu-right navbar-dropdown dropdown-menu-custom-style" aria-labelledby="{{ $id }}">
        @can('update-admin-profile')
            <a class="dropdown-item" href="{{ route('edit-profile') }}">
                <x-icon name="user" :size="18" />{{ __('update_profile') }}</a>
            <div class="dropdown-divider"></div>
        @endcan
        <a class="dropdown-item" href="{{ route('resetpassword') }}">
            <x-icon name="refresh" :size="18" />{{ __('change_password') }}</a>
        <div class="dropdown-divider"></div>
        {{-- A form, so another site can't sign people out with a link or image. --}}
        <form action="{{ route('logout') }}" method="POST" class="m-0" data-year-safe>
            @csrf
            <button type="submit" class="dropdown-item">
                <x-icon name="sign-out" :size="18" /> {{ __('signout') }}
            </button>
        </form>
    </div>
</li>
