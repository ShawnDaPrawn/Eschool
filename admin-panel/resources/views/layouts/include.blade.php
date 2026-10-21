@php
    $lang = Session::get('language');
@endphp
{{-- Material 3 design tokens, generated from the apps' theme (packages/eschool_core). Load first. --}}
<link rel="stylesheet" href="{{ asset('/assets/css/m3-tokens.css') }}?v={{ filemtime(public_path('assets/css/m3-tokens.css')) }}">
<link rel="stylesheet" href="{{ asset('/assets/css/vendor.bundle.base.css') }}" async>

<link rel="stylesheet" href="{{ asset('/assets/fonts/font-awesome.min.css') }}" />
<link rel="stylesheet" href="{{ asset('/assets/select2/select2.min.css') }}" async>
<link rel="stylesheet" href="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/color-picker/color.min.css') }}" async>
<link rel="stylesheet" href="{{ asset('/assets/jodit/jodit.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/katex/katex.min.css') }}">
@if ($lang)
    @if ($lang->is_rtl)
        <link rel="stylesheet" href="{{ asset('/assets/css/rtl.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('/assets/css/style.css') }}">
    @endif
@else
    <link rel="stylesheet" href="{{ asset('/assets/css/style.css') }}">
@endif
{{-- Typeface and legacy colour variables mapped to the tokens — after style/rtl, before the redesign sheets. --}}
<link rel="stylesheet" href="{{ asset('/assets/css/m3-base.css') }}?v={{ filemtime(public_path('assets/css/m3-base.css')) }}">
{{-- Admin chrome redesign (sidebar + topbar) — loaded after style/rtl so they override the legacy rules in both. --}}
<link rel="stylesheet" href="{{ asset('/assets/css/sidebar.css') }}?v={{ filemtime(public_path('assets/css/sidebar.css')) }}">
<link rel="stylesheet" href="{{ asset('/assets/css/header.css') }}?v={{ filemtime(public_path('assets/css/header.css')) }}">
{{-- Content-area redesign (cards, buttons, form-fields, footer, content-wrapper, Select2) — loaded after style/rtl so it lands in BOTH bundles; RTL flips scoped via [dir="rtl"]. --}}
<link rel="stylesheet" href="{{ asset('/assets/css/content.css') }}?v={{ filemtime(public_path('assets/css/content.css')) }}">
{{-- Timetable day-card redesign (class & teacher timetable) — token-driven, loaded after content.css. --}}
<link rel="stylesheet" href="{{ asset('/assets/css/timetable.css') }}?v={{ filemtime(public_path('assets/css/timetable.css')) }}">
{{-- Class-subject edit form layout (class/edit_subject) — token-driven, loaded after content.css. --}}
<link rel="stylesheet" href="{{ asset('/assets/css/class-subject.css') }}?v={{ filemtime(public_path('assets/css/class-subject.css')) }}">
<link rel="stylesheet" href="{{ asset('/assets/css/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/css/flatpickr-theme.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/css/ekko-lightbox.css') }}">

<link rel="stylesheet" href="{{ asset('/assets/bootstrap-table/bootstrap-table.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/bootstrap-table/fixed-columns.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/bootstrap-table/reorder-rows.css') }}">
{{-- Bootstrap-table redesign (toolbar, header, rows, row actions, pagination, dropdowns) — loaded AFTER the plugin sheets so it outranks both them and the legacy rules in style/rtl; RTL flips scoped via [dir="rtl"]. --}}
<link rel="stylesheet" href="{{ asset('/assets/css/tables.css') }}?v={{ filemtime(public_path('assets/css/tables.css')) }}">

<script src="{{ route('common.language.read') }}"></script>

{{-- <link rel="shortcut icon" href="{{asset(config('global.LOGO_SM')) }}" /> --}}
<link rel="shortcut icon" href="{{ schoolSetting('favicon') ? url(Storage::url(schoolSetting('favicon'))) : asset('assets/logo.svg') }}" />
@php
    $login_image = getSettings('login_image');
    if ($login_image != null) {
        $path = $login_image['login_image'];
        $login_image = url(Storage::url($path));
    } else {
        $login_image = asset('assets/eschool.jpg');
    }

@endphp
{{-- Colours come from the design tokens (m3-tokens.css), not the theme_color / secondary_color settings. --}}
<style>
    :root {
        --image-url: url(<?= $login_image ?>);
    }
</style>
<script>
    var baseUrl = "{{ URL::to('/') }}";
    // Try the placeholder once; a missing placeholder must not retry forever.
    const onErrorImage = (e) => {
        e.target.onerror = null;
        e.target.src = "{{ asset('assets/no-image.svg') }}";
    };
    // Fallback for user/profile avatars (people) — a generic avatar instead of the "no image" placeholder.
    const onErrorAvatar = (e) => {
        e.target.onerror = null;
        e.target.src = "{{ asset('assets/avatar.png') }}";
    };
</script>
