@extends('saas.layout')

@section('title', __('LURITS claims'))

@section('content')
<h1>{{ __('LURITS claims') }}</h1>
<p class="saas-lead">{{ __('A school tried to record a LURITS number that another learner already holds. Work out with the schools whether the number was mistyped or the learner is moving, then close the claim. Learners\' names and numbers are never shown here, and nothing is merged automatically.') }}</p>
@if (session('success'))<p role="status" class="saas-card">{{ session('success') }}</p>@endif
@if ($errors->any())<p role="alert" class="saas-card">{{ $errors->first() }}</p>@endif

<h2>{{ __('Open') }}</h2>
@if ($open === [])
    <p class="saas-card">{{ __('No open claims.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Open claims') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Raised') }}</th><th scope="col">{{ __('Claiming school') }}</th><th scope="col">{{ __('Number held at') }}</th><th scope="col">{{ __('Source') }}</th><th scope="col">{{ __('Close') }}</th></tr></thead>
        <tbody>
        @foreach ($open as $claim)
            <tr>
                <td>{{ $claim['raised_at'] }}</td><td>{{ $claim['school'] }}</td><td>{{ $claim['holder_schools'] }}</td><td>{{ $claim['source'] }}</td>
                <td>
                    <form method="POST" action="{{ route('saas.lurits.resolve', $claim['id']) }}" aria-label="{{ __('Close claim :id', ['id' => $claim['id']]) }}">
                        @csrf
                        <label for="outcome-{{ $claim['id'] }}" class="m3-visually-hidden">{{ __('Outcome') }}</label>
                        <select id="outcome-{{ $claim['id'] }}" name="outcome" required>
                            <option value="corrected">{{ __('A school corrected the number') }}</option>
                            <option value="transfer">{{ __('The learner is moving: transfer started') }}</option>
                            <option value="rejected">{{ __('The claim was wrong') }}</option>
                        </select>
                        <button class="saas-btn saas-btn--quiet" type="submit">{{ __('Close the claim') }}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if ($closed !== [])
<h2>{{ __('Recently closed') }}</h2>
<ul>
    @foreach ($closed as $claim)<li>{{ $claim['raised_at'] }} · {{ $claim['school'] }} · {{ $claim['resolution'] }}</li>@endforeach
</ul>
@endif
@endsection
