<nav class="workspace-tabs" aria-label="{{ __('Alumni workspace') }}">
    @can('people-pool-manage')
    <a class="m3-button m3-button--text" href="{{ route('people-pool-panel.index') }}" @if(request()->routeIs('people-pool-panel.index')) aria-current="page" @endif>{{ __('Alumni and volunteers') }}</a>
    <a class="m3-button m3-button--text" href="{{ route('people-pool-panel.create') }}" @if(request()->routeIs('people-pool-panel.create')) aria-current="page" @endif>{{ __('Invite a member') }}</a>
    <a class="m3-button m3-button--text" href="{{ route('people-pool-panel.bookings') }}" @if(request()->routeIs('people-pool-panel.bookings')) aria-current="page" @endif>{{ __('Group bookings') }}</a>
    @endcan
</nav>
@if(session('success'))<div class="m3-banner m3-banner--success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="m3-banner m3-banner--danger" role="alert"><h2>{{ __('Review the following before continuing') }}</h2><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
