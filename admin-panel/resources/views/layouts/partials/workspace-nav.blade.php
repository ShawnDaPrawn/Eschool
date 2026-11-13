@php
    $workspaceNavigation = app(\App\Services\Panel\Navigation::class);
    $currentWorkspaceItem = $workspaceNavigation->current(auth()->user());
@endphp
{{-- Source pages without the shared header still need one navigation trail. --}}
@if($currentWorkspaceItem && !request()->routeIs('panel.workspace') && !str_contains(view()->getSections()['content'] ?? '', 'data-workspace-page-header'))
    <nav class="panel-local-nav panel-local-nav--context" aria-label="{{ __('Module navigation') }}">
        <a href="{{ $workspaceNavigation->workspaceUrl($currentWorkspaceItem['section']) }}">{{ $currentWorkspaceItem['section_label'] }}</a>
        @if($currentWorkspaceItem['group'])
            <a href="{{ $workspaceNavigation->workspaceUrl($currentWorkspaceItem['section'], $currentWorkspaceItem['group']) }}">{{ $currentWorkspaceItem['group_label'] }}</a>
        @endif
        <span aria-current="page">{{ $currentWorkspaceItem['label'] }}</span>
    </nav>
@endif
