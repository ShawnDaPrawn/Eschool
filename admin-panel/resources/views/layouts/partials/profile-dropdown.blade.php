{{--
    Admin topbar profile dropdown (avatar + menu).
    Shared between the desktop and mobile navbar so the markup — and the
    Lucide <x-icon> swap — lives in one place. Pass a unique $id per usage
    (the desktop/mobile copies previously shared id="profileDropdown",
    which produced a duplicate DOM id).
--}}
@props(['id' => 'profileDropdown'])
<li class="nav-item nav-profile dropdown">
    <a class="nav-link dropdown-toggle" id="{{ $id }}" href="#" data-toggle="dropdown" aria-expanded="false">
        <div class="nav-profile-img">
            <img src="{{ Auth::user()->image }}" alt="image" onerror="onErrorAvatar(event)">
        </div>
        <div class="nav-profile-text">
            <p class="mb-0 text-black">{{ Auth::user()->first_name }}</p>
        </div>
    </a>
    <div class="dropdown-menu navbar-dropdown dropdown-menu-custom-style" aria-labelledby="{{ $id }}">
        @can('update-admin-profile')
            <a class="dropdown-item" href="{{ route('edit-profile') }}">
                <x-icon name="user" :size="18" />{{ __('update_profile') }}</a>
            <div class="dropdown-divider"></div>
        @endcan
        <a class="dropdown-item" href="{{ route('resetpassword') }}">
            <x-icon name="refresh" :size="18" />{{ __('change_password') }}</a>
        <div class="dropdown-divider"></div>
        {{-- A form, so another site can't sign people out with a link or image. --}}
        <form action="{{ route('logout') }}" method="POST" class="m-0">
            @csrf
            <button type="submit" class="dropdown-item">
                <x-icon name="sign-out" :size="18" /> {{ __('signout') }}
            </button>
        </form>
    </div>
</li>
