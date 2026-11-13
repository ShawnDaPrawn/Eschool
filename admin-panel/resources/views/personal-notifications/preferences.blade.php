@extends('layouts.master')
@section('title', __('My notification channels'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('My notification channels')" :subtitle="__('Change your own offered channels. Required notices remain enabled.')" />
    @include('personal-notifications.nav')
    <form class="page-filters" method="GET" action="{{ route('panel.preferences.notifications') }}">
        <x-page.field name="q" :label="__('Find a notice type')" :value="$query" :restore-old-input="false" maxlength="120" />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('Search') }}</button>
        @if($query !== '')<a class="m3-button m3-button--text" href="{{ route('panel.preferences.notifications') }}">{{ __('Clear search') }}</a>@endif
    </form>
    @forelse($groups as $module => $preferences)
    <details class="personal-preference-group" @if($query !== '' || $loop->first) open @endif>
        <summary><strong>{{ __(config('modules.'.$module.'.label', ucfirst(str_replace('_', ' ', $module)))) }}</strong><span>{{ __(':count notice types', ['count' => count($preferences)]) }}</span></summary>
        @foreach($preferences as $preference)
        <section class="panel-record-card">
            <h2 class="page-card-title">{{ $preference['label'] }}</h2>
            <div class="panel-form-grid">
                @foreach($preference['channels'] as $channel => $choice)
                    @if($choice['locked'])
                        <div><strong>{{ __(ucfirst($channel)) }}</strong><p>{{ __('Required') }}</p><p>{{ $choice['reason'] }}</p></div>
                    @else
                        <x-page.form-card :id="'channel-'.str_replace('.', '-', $preference['type']).'-'.$channel" :action="route('panel.preferences.notifications.update')" :title="__(ucfirst($channel))" :submit-label="__('Save this choice')" :draft="false" :write="false">
                            @include('personal-notifications.context')
                            <input type="hidden" name="type" value="{{ $preference['type'] }}">
                            <input type="hidden" name="channel" value="{{ $channel }}">
                            <x-page.field :id="'choice-'.str_replace('.', '-', $preference['type']).'-'.$channel" name="enabled" type="select" :label="__('Receive this notice')" :options="[1 => __('On'), 0 => __('Off')]" :value="(int) $choice['enabled']" required />
                        </x-page.form-card>
                    @endif
                @endforeach
            </div>
        </section>
        @endforeach
    </details>
    @empty
        <x-page.empty-state :title="$query !== '' ? __('No notice types match') : __('No optional channels')" :message="$query !== '' ? __('Try another search or clear the filter.') : __('Your school and role determine which notice choices are available.')" />
    @endforelse
</div>
@endsection
