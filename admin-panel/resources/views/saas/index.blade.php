@extends('saas.layout')

@section('title', __('Schools'))

@section('content')
<h1>{{ __('Schools') }}</h1>
<p class="saas-lead">{{ __('Every school on this install. Only counts are shown here: no learner, parent or staff record is opened from the platform area.') }}</p>
<div class="saas-card">
    <table class="saas-table">
        <caption>{{ __('Schools, their package and the number of active people by role') }}</caption>
        <thead>
        <tr>
            <th scope="col">{{ __('School') }}</th>
            <th scope="col">{{ __('Status') }}</th>
            <th scope="col">{{ __('Stage') }}</th>
            <th scope="col">{{ __('Package') }}</th>
            <th scope="col" class="num">{{ __('Learners') }}</th>
            <th scope="col" class="num">{{ __('Parents') }}</th>
            <th scope="col" class="num">{{ __('Teachers') }}</th>
            <th scope="col" class="num">{{ __('People') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($schools as $school)
            <tr>
                <th scope="row"><a href="{{ route('saas.schools.show', $school['id']) }}">{{ $school['name'] }}</a></th>
                <td><span class="saas-badge {{ $school['status'] === 'active' ? '' : 'saas-badge--warn' }}">{{ __(ucfirst($school['status'])) }}</span></td>
                <td>{{ __('stage_'.$school['stage']) }}</td>
                <td>{{ $school['package_key'] ?? __('Upgraded seed (legacy)') }}</td>
                <td class="num">{{ $school['roles']['Student'] }}</td>
                <td class="num">{{ $school['roles']['Parent'] }}</td>
                <td class="num">{{ $school['roles']['Teacher'] }}</td>
                <td class="num">{{ $school['people'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@if (app(\App\Services\Platform\Saas\PlatformStaffAccess::class)->can(auth()->user(), 'schools.create'))
<p class="saas-actions"><a class="saas-btn" href="{{ route('saas.schools.create') }}">{{ __('Set up a school') }}</a></p>
@endif
@endsection
