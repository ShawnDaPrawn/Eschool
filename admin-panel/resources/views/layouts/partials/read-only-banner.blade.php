{{--
    Read-only banner: shown on every panel page while the header's year switcher
    shows a year other than the school's current one. The server refuses writes
    in that state (EnforceReadOnlyYears); page.js disables the write controls.
--}}
@php
    $panelYear = $panelYear ?? app(\App\Services\Panel\SessionYearContext::class);
@endphp
@if ($panelYear->isReadOnly())
    <div class="year-banner" role="status" data-read-only-year="{{ $panelYear->selectedId() }}"
        data-current-year="{{ $panelYear->currentId() }}">
        <x-icon name="lock" :size="20" class="year-banner-icon" />
        <p class="year-banner-text">
            <strong>{{ __($panelYear->selectedIsUpcoming() ? 'read_only_year_upcoming_title' : 'read_only_year_title', ['year' => $panelYear->selected()?->name]) }}</strong>
            <span>{{ __('read_only_year_explained', ['current' => $panelYear->current()?->name]) }}</span>
        </p>
        <form method="POST" action="{{ route('set.session.year') }}" class="year-banner-action" data-year-safe>
            @csrf
            <input type="hidden" name="session_year_id" value="{{ $panelYear->currentId() }}">
            <button type="submit" class="btn btn-sm btn-theme">
                {{ __('back_to_current_year', ['year' => $panelYear->current()?->name]) }}
            </button>
        </form>
    </div>
@endif
