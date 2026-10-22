{{--
    Account menu in the panel header (avatar, name, profile, password, sign out):
    a Material 3 menu opened by data-m3-menu (m3.js).
--}}
@props(['id' => 'profileDropdown'])
<button type="button" class="panel-account" id="{{ $id }}" data-m3-menu="{{ $id }}-menu" aria-haspopup="menu"
    aria-expanded="false" aria-label="{{ __('nav_account_menu', ['name' => Auth::user()->first_name]) }}">
    {{-- No photo yet: the generic avatar, rather than a request for an empty storage path. --}}
    <img class="panel-account__avatar" src="{{ Auth::user()->getRawOriginal('image') ? Auth::user()->image : asset('assets/avatar.png') }}" alt=""
        onerror="onErrorAvatar(event)">
    <span class="panel-account__name">{{ Auth::user()->first_name }}</span>
    <x-icon name="chevron-down" :size="18" class="panel-account__chevron" />
</button>
<div class="m3-menu panel-menu" id="{{ $id }}-menu" popover role="menu" aria-labelledby="{{ $id }}">
    @can('update-admin-profile')
        <a class="m3-menu__item" role="menuitem" href="{{ route('edit-profile') }}">
            <x-icon name="user" :size="24" />{{ __('update_profile') }}</a>
    @endcan
    <a class="m3-menu__item" role="menuitem" href="{{ route('resetpassword') }}">
        <x-icon name="refresh" :size="24" />{{ __('change_password') }}</a>
    <hr class="m3-menu__divider">
    {{-- A form, so another site can't sign people out with a link or image. --}}
    <form action="{{ route('logout') }}" method="POST" class="panel-menu__form" data-year-safe>
        @csrf
        <button type="submit" class="m3-menu__item" role="menuitem">
            <x-icon name="sign-out" :size="24" />{{ __('signout') }}
        </button>
    </form>
</div>
