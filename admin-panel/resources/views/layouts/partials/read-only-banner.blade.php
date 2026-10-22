{{--
    Read-only banner (an M3 banner): shown on every panel page while the
    header's year switcher shows a year other than the school's current one.
    The server refuses writes in that state (EnforceReadOnlyYears); panel.js
    disables the write controls.
--}}
@php
    $panelYear = $panelYear ?? app(\App\Services\Panel\SessionYearContext::class);
@endphp
@if ($panelYear->isReadOnly())
    <div class="m3 m3-banner m3-banner--warning year-banner" role="status" data-read-only-year="{{ $panelYear->selectedId() }}"
        data-current-year="{{ $panelYear->currentId() }}">
        <x-icon name="lock" :size="24" class="year-banner__icon" />
        <p class="year-banner__text">
            <strong>{{ __($panelYear->selectedIsUpcoming() ? 'read_only_year_upcoming_title' : 'read_only_year_title', ['year' => $panelYear->selected()?->name]) }}</strong>
            <span>{{ __('read_only_year_explained', ['current' => $panelYear->current()?->name]) }}</span>
        </p>
        <form method="POST" action="{{ route('set.session.year') }}" class="year-banner__action" data-year-safe>
            @csrf
            <input type="hidden" name="session_year_id" value="{{ $panelYear->currentId() }}">
            <button type="submit" class="m3-button m3-button--filled">
                {{ __('back_to_current_year', ['year' => $panelYear->current()?->name]) }}
            </button>
        </form>
    </div>
@endif
