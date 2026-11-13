<nav class="panel-section-nav" aria-label="{{ __('My notifications') }}">
    @foreach(['panel.inbox.index' => 'Inbox', 'panel.preferences.notifications' => 'Notification channels', 'panel.preferences.quiet' => 'Quiet hours', 'panel.preferences.texts' => 'SMS and WhatsApp'] as $route => $label)
        <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>{{ __($label) }}</a>
    @endforeach
</nav>
