@extends('layouts.master')
@section('title', __('Who can see what'))
@section('content')
@php
    $levelLabels = ['none' => __('No access'), 'view' => __('View'), 'contribute' => __('Contribute'), 'manage' => __('Manage')];
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Who can see what')" :subtitle="__('Check one person’s or one role’s access, module by module, and the pages they would see. Nothing is changed here.')" />
    <div class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
        <form method="GET" action="{{ route('access-review.index') }}" aria-label="{{ __('Review a person') }}">
            <x-page.field name="person" id="review-person" type="select" :label="__('Person')" :value="$selected['person'] ?? null" :options="$choices['people']" :placeholder="__('Choose a person')" :restore-old-input="false" required />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Review person') }}</button>
        </form>
        <form method="GET" action="{{ route('access-review.index') }}" aria-label="{{ __('Review a role') }}">
            <x-page.field name="role" id="review-role" type="select" :label="__('Role')" :value="$selected['role'] ?? null" :options="$choices['roles']" :placeholder="__('Choose a role')" :restore-old-input="false" required />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Review role') }}</button>
        </form>
    </div></div>

    @if ($result)
        <section class="page-section" aria-labelledby="review-title">
            <h2 id="review-title">{{ $result['subject']['name'] }}</h2>
            <p>{{ $result['subject']['kind'] === 'person' ? __('Roles at this school: :roles', ['roles' => implode(', ', array_map(fn ($r) => __($r), $result['subject']['roles']))]) : __('Role') }}</p>
            <div class="m3-card m3-card--outlined"><div class="m3-card__content">
                <div class="page-table__scroll" role="region" aria-label="{{ __('Access in each module') }}" tabindex="0"><table class="page-table__table">
                    <caption>{{ __('Access in each module') }}</caption>
                    <thead><tr><th scope="col">{{ __('Module') }}</th><th scope="col">{{ __('Level') }}</th><th scope="col">{{ __('Abilities') }}</th></tr></thead>
                    <tbody>
                    @foreach ($result['modules'] as $module)
                        <tr>
                            <th scope="row">{{ $module['name'] }}@unless ($module['enabled']) <span>({{ __('switched off') }})</span>@endunless</th>
                            <td>{{ $levelLabels[$module['level']] }}</td>
                            <td>@foreach ($module['abilities'] as $ability)<span @class(['page-badge', 'page-badge--warn' => $ability['sensitive']])>{{ $ability['label'] }}@if ($ability['sensitive']) ({{ __('sensitive') }})@endif</span> @endforeach</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            </div></div>
            @if ($result['other'] !== [])
                <h3>{{ __('Other permissions') }}</h3>
                <p>{{ __('Held directly, outside the module levels.') }}</p>
                <ul>@foreach ($result['other'] as $permission)<li>{{ $permission }}</li>@endforeach</ul>
            @endif
            <h3>{{ __('Pages in their menu') }}</h3>
            @if ($result['subject']['kind'] === 'role')
                <p>{{ __('Pages marked “depends on appointment” also need a grade, class, caseload or other appointment.') }}</p>
            @endif
            <ul>
                @forelse ($result['pages'] as $page)
                    <li>{{ $page['label'] }}@if ($page['conditional']) · {{ __('depends on appointment') }}@endif</li>
                @empty
                    <li>{{ __('No panel pages') }}</li>
                @endforelse
            </ul>
        </section>
    @endif
</div>
@endsection
