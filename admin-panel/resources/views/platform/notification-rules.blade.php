@extends('layouts.master')
@section('title', __('Notification rules'))
@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('Notification rules')" />
    <p>{{ __('Choose which notices this school offers on each portal. Account safety, urgent notices and guardian absence alerts stay required.') }}</p>
    <form method="GET" action="{{ route('notification-rules.index') }}">
        <label for="notification-portal">{{ __('Portal') }}</label>
        <select id="notification-portal" name="portal">
            @foreach (['learner', 'parent', 'teacher', 'admin'] as $choice)
                <option value="{{ $choice }}" @selected($portal === $choice)>{{ __(ucfirst($choice)) }}</option>
            @endforeach
        </select>
        <button class="m3-button m3-button--outlined" type="submit">{{ __('View rules') }}</button>
    </form>
    @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    <div class="m3 m3-card m3-card--outlined page-table">
        <div class="page-table__scroll" tabindex="0" aria-label="{{ __('Notification rules') }}">
        <table class="page-table__table">
            <caption>{{ __('Notification channels for this portal') }}</caption>
            <thead><tr><th scope="col">{{ __('Notice') }}</th><th scope="col">{{ __('Push') }}</th><th scope="col">{{ __('Email') }}</th><th scope="col">{{ __('In app') }}</th></tr></thead>
            <tbody>
            @foreach ($types as $type => $definition)
                <tr><th scope="row">{{ __($definition['label']) }}</th>
                @foreach ($definition['modes'] as $channel => $mode)
                    <td>
                    @if (in_array($portal, $definition['locked'] ?? [], true))
                        {{ __($mode === 'mandatory' ? 'Required by NavigateEd' : 'Not offered') }}
                    @else
                        <form method="POST" action="{{ route('notification-rules.update') }}">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type }}">
                            <input type="hidden" name="portal" value="{{ $portal }}">
                            <input type="hidden" name="channel" value="{{ $channel }}">
                            <select name="mode" aria-label="{{ __($definition['label']) }} {{ $channel }}">
                                @foreach (['mandatory' => 'Required', 'default_on' => 'On by default', 'default_off' => 'Off by default', 'off' => 'Not offered'] as $value => $label)
                                    <option value="{{ $value }}" @selected($mode === $value)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <button class="m3-button m3-button--text" type="submit" aria-label="{{ __('Save') }} {{ __($definition['label']) }} {{ $channel }}">{{ __('Save') }}</button>
                        </form>
                    @endif
                    </td>
                @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
