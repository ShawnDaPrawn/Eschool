@extends('layouts.master')

@section('title', __('School modules'))

@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('School modules')"><x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('school-modules.readiness') }}">{{ __('Check setup readiness') }}</a></x-slot:actions></x-page.header>
    <p>{{ __('Choose the features available at this school. Required features stay on. Financial history remains readable when fees are switched off.') }}</p>
    @if ($errors->any())
        <div class="m3-banner m3-banner--danger" role="alert">{{ $errors->first() }}</div>
    @endif
    @if (session('success'))
        <p role="status">{{ session('success') }}</p>
    @endif
    <div class="m3 m3-card m3-card--outlined panel-table-scroll page-table">
        <table class="page-table__table">
            <caption>{{ __('Module availability and package entitlement') }}</caption>
            <thead><tr><th scope="col">{{ __('Module') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Change') }}</th></tr></thead>
            <tbody>
            @foreach ($modules as $key => $module)
                <tr>
                    <th scope="row">{{ __($module['name']) }}</th>
                    <td>{{ __($module['read_only'] && $module['enabled'] ? 'Read-only' : ($module['enabled'] ? 'On' : 'Off')) }}</td>
                    <td>
                        @if ($module['always_on'] ?? false)
                            {{ __('Required') }}
                        @elseif (! $module['in_package'])
                            {{ __('Not in this package') }}
                        @else
                            <form method="POST" action="{{ route('school-modules.update', $key) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="enabled" value="{{ $module['enabled'] && ! $module['read_only'] ? '0' : '1' }}">
                                <button class="m3-button m3-button--outlined" type="submit" aria-label="{{ __($module['enabled'] && ! $module['read_only'] ? 'Switch off' : 'Enable') }} {{ __($module['name']) }}">{{ __($module['enabled'] && ! $module['read_only'] ? 'Switch off' : 'Enable') }}</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
