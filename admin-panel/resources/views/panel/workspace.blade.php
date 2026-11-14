@extends('layouts.master')
@section('title', $selectedGroup['label'] ?? $workspace['label'])
@section('content')
<div class="content-wrapper m3 page-content panel-workspace" data-workspace="{{ $workspace['key'] }}">
    <x-page.header :title="$selectedGroup['label'] ?? $workspace['label']"
        :subtitle="$mode === 'configuration' ? __('Configure this module separately from daily school work.') : __('Choose a school task, open its records and continue with the permitted actions.')">
        <x-slot:actions>
            @if($hasOperations && $mode !== 'operations')
                <a class="m3-button m3-button--outlined" href="{{ route('panel.workspace', ['section' => $workspace['key'], 'group' => $selectedGroup['key'] ?? null]) }}">{{ __('Daily work') }}</a>
            @endif
            @if($hasConfiguration && $mode !== 'configuration')
                <a class="m3-button m3-button--outlined" href="{{ route('panel.workspace', ['section' => $workspace['key'], 'group' => $selectedGroup['key'] ?? null, 'view' => 'configuration']) }}">{{ __('Configuration') }}</a>
            @endif
        </x-slot:actions>
    </x-page.header>
    @if(count($workspace['groups']) > 1)
        <nav class="panel-local-nav" aria-label="{{ __('Module sections') }}">
            <a href="{{ route('panel.workspace', ['section' => $workspace['key'], 'view' => $mode]) }}" @unless($selectedGroup) aria-current="page" @endunless>{{ __('Overview') }}</a>
            @foreach($workspace['groups'] as $entry)
                <a href="{{ route('panel.workspace', ['section' => $workspace['key'], 'group' => $entry['key'], 'view' => $mode]) }}" @if(($selectedGroup['key'] ?? null) === $entry['key']) aria-current="page" @endif>{{ $entry['label'] }}</a>
            @endforeach
        </nav>
    @endif
    <form method="GET" class="page-filters" aria-label="{{ __('Find a module task') }}">
        <input type="hidden" name="view" value="{{ $mode }}">
        <x-page.field name="q" type="search" :label="__('Find a task')" :value="$search" maxlength="100" />
        <button type="submit" class="m3-button m3-button--outlined">{{ __('Search') }}</button>
        @if($search !== '')<a class="m3-button m3-button--text" href="{{ route('panel.workspace', ['section' => $workspace['key'], 'group' => $selectedGroup['key'] ?? null, 'view' => $mode]) }}">{{ __('Clear search') }}</a>@endif
    </form>
    <div class="panel-workspace__grid">
        @forelse($displayGroups as $entry)
            <section class="m3-card m3-card--outlined panel-workspace__card" aria-labelledby="workspace-{{ $entry['key'] }}">
                <div class="m3-card__content">
                    <h2 id="workspace-{{ $entry['key'] }}">{{ $entry['label'] }}</h2>
                    <ul class="m3-list m3-list--divided">
                        @foreach($entry['items'] as $item)
                            <li class="m3-list__item">
                                <a class="panel-workspace__task" data-workspace-item="{{ $item['key'] }}" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @empty
            <x-page.empty-state :title="$search !== '' ? __('No matching tasks') : __('No tasks in this view')"
                :message="$search !== '' ? __('Try another name or clear the search.') : __('Available tasks depend on your school modules and permissions. Use the other workspace view when available.')" />
        @endforelse
    </div>
</div>
@endsection
