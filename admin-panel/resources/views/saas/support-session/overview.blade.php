@extends('saas.support-session.layout')

@section('title', __('Overview'))

@section('content')
<h1>{{ $school['name'] }}</h1>
<dl class="saas-card">
    <dt>{{ __('Status') }}</dt><dd>{{ ucfirst($school['status']) }}</dd>
    <dt>{{ __('Package') }}</dt><dd>{{ $school['package'] ?? __('Legacy') }}</dd>
    <dt>{{ __('Time zone') }}</dt><dd>{{ $school['timezone'] }}</dd>
    <dt>{{ __('Current year') }}</dt><dd>{{ $year ?? __('None set') }}</dd>
</dl>

<h2>{{ __('Set-up') }}</h2>
<ul class="saas-card">
    <li>{{ trans_choice('{0} No classes|{1} 1 class|[2,*] :count classes', $structure['classes'], ['count' => $structure['classes']]) }}</li>
    <li>{{ trans_choice('{0} No class sections|{1} 1 class section|[2,*] :count class sections', $structure['sections'], ['count' => $structure['sections']]) }}</li>
    <li>{{ trans_choice('{0} No subjects|{1} 1 subject|[2,*] :count subjects', $structure['subjects'], ['count' => $structure['subjects']]) }}</li>
    <li>{{ trans_choice('{0} No staff invitation waiting|{1} 1 staff invitation waiting|[2,*] :count staff invitations waiting', $structure['invitations'], ['count' => $structure['invitations']]) }}</li>
</ul>

<h2>{{ __('People with an active role') }}</h2>
@if ($roles === [])
    <p class="saas-card">{{ __('Nobody has an active role.') }}</p>
@else
<ul class="saas-card">
    @foreach ($roles as $role => $people)<li>{{ $role }}: {{ $people }}</li>@endforeach
</ul>
@endif

<h2>{{ __('Modules') }}</h2>
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Modules') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr><th scope="col">{{ __('Module') }}</th><th scope="col">{{ __('State') }}</th></tr></thead>
        <tbody>
        @foreach ($modules as $module)
            <tr><th scope="row">{{ $module['name'] }}</th><td>{{ ['on' => __('On'), 'off' => __('Off'), 'read_only' => __('Read only')][$module['state']] }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
