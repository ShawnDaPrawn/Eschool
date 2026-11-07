@extends('saas.layout')

@section('title', __('School groups'))

@section('content')
@php $canManage = app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), 'groups.manage'); @endphp
<h1>{{ __('School groups') }}</h1>
<p class="saas-lead">{{ __('One owner, many schools. A school joins a group only when its own Super Admin agrees, and can leave at any time. Group administrators see counts and shares across the group\'s schools, never a learner\'s record.') }}</p>

@if ($groups === [])
    <p class="saas-card">{{ __('No school groups yet.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('School groups') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Group') }}</th><th scope="col" class="num">{{ __('Schools in it') }}</th><th scope="col" class="num">{{ __('Invited, not yet answered') }}</th><th scope="col" class="num">{{ __('Group administrators') }}</th></tr></thead>
        <tbody>
        @foreach ($groups as $group)
            <tr data-group="{{ $group['id'] }}">
                <th scope="row"><a href="{{ route('saas.groups.show', $group['id']) }}">{{ $group['name'] }}</a></th>
                <td class="num">{{ $group['schools'] }}</td>
                <td class="num">{{ $group['invited'] }}</td>
                <td class="num">{{ $group['admins'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if ($canManage)
<form method="POST" action="{{ route('saas.groups.store') }}" class="saas-card" aria-label="{{ __('Create a group') }}">
    @csrf
    <h2>{{ __('Create a group') }}</h2>
    <div class="saas-grid">
        <div class="saas-field">
            <label for="group-name">{{ __('Name') }}</label>
            <input id="group-name" name="name" required minlength="3" maxlength="120" value="{{ old('name') }}">
        </div>
        <div class="saas-field">
            <label for="group-notes">{{ __('Notes (HQ only)') }}</label>
            <textarea id="group-notes" name="notes" maxlength="500" rows="2">{{ old('notes') }}</textarea>
        </div>
    </div>
    <div class="saas-actions"><button class="saas-btn" type="submit">{{ __('Create the group') }}</button></div>
</form>
@endif
@endsection
