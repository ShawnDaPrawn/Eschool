@extends('layouts.master')
@section('title', __('School terms'))
@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('School terms')" :subtitle="__('Manage calendar dates and grade ranges for a school year.')" />
    @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    <section class="m3 m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">
        <form method="GET" action="{{ route('term.index') }}">
            <x-page.field id="calendar-year" name="session_year_id" type="select" :label="__('School year')" :value="$year?->id" :options="$years->pluck('name', 'id')->all()" required />
            <button class="m3-button m3-button--outlined" type="submit">{{ __('Show calendar') }}</button>
        </form>
    </div></section>
    @if ($year)
        @php($grades = collect(range(0, 12))->mapWithKeys(fn ($g) => [$g => __('Grade').' '.($g === 0 ? 'R' : $g)])->all())
        <p>{{ __('Optional lock and report dates prepare the calendar; assessment enforcement and report release are separate features.') }}</p>
        <h2>{{ __('Calendar entries') }}</h2>
        @forelse ($terms as $row)
            <section class="m3 m3-card m3-card--outlined page-form-card" data-term-id="{{ $row['id'] }}"><div class="m3-card__content page-card__content">
                <h3>{{ $row['name'] }}</h3>
                @if ($row['needs_review'])<p role="status">{{ __('Imported term dates need review.') }}</p>@endif
                <p>{{ $row['starts_on'] }} — {{ $row['ends_on'] }} · {{ $grades[$row['grade_from']] }} — {{ $grades[$row['grade_to']] }}</p>
                <dl><dt>{{ __('Marks lock date (optional)') }}</dt><dd>{{ $row['marks_lock_on'] ?? __('Not set') }}</dd>
                <dt>{{ __('Report date (optional)') }}</dt><dd>{{ $row['reports_on'] ?? __('Not set') }}</dd></dl>
                @can('term-edit')
                    <details><summary>{{ __('Edit term') }}: {{ $row['name'] }}</summary>
                        <form method="POST" action="{{ route('term.update', $row['id']) }}">@csrf @method('PUT')
                            @include('term.fields', ['prefix' => 'term-'.$row['id']])
                            <button class="m3-button m3-button--filled" type="submit">{{ __('Save term') }}</button>
                        </form>
                    </details>
                @endcan
                @can('term-delete')
                    <form method="POST" action="{{ route('term.destroy', $row['id']) }}">@csrf @method('DELETE')
                        <button class="m3-button m3-button--text" type="submit" aria-label="{{ __('Remove term') }} {{ $row['name'] }}">{{ __('Remove term') }}</button>
                    </form>
                @endcan
            </div></section>
        @empty
            <p role="status">{{ __('No terms yet. Add dates or preview a calendar template.') }}</p>
        @endforelse
        @can('term-create')
            <section class="m3 m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">
                <h2>{{ __('Add term') }}</h2>
                <form method="POST" action="{{ route('term.store') }}">@csrf
                    @include('term.fields', ['prefix' => 'new-term', 'row' => []])
                    <button class="m3-button m3-button--filled" type="submit">{{ __('Add term') }}</button>
                </form>
                <h2>{{ __('Save this calendar as a template') }}</h2>
                <form method="POST" action="{{ route('term.templates.store') }}">@csrf
                    <input type="hidden" name="session_year_id" value="{{ $year->id }}">
                    <x-page.field id="template-name" name="name" :label="__('Template name')" required maxlength="120" />
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Save calendar template') }}</button>
                </form>
            </div></section>
        @endcan
        <section class="m3 m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">
            <h2>{{ __('School calendar templates') }}</h2>
            @if ($templates->isEmpty())<p>{{ __('No calendar templates saved yet.') }}</p>@else
                <p>{{ __('Preview first. Applying adds missing terms and never replaces existing dates.') }}</p>
                <form method="POST" action="{{ route('term.templates.apply') }}">@csrf
                    <input type="hidden" name="session_year_id" value="{{ $year->id }}">
                    <x-page.field id="apply-template" name="template_id" type="select" :label="__('Calendar template')" :options="$templates->pluck('name', 'id')->all()" required />
                    <button class="m3-button m3-button--outlined" type="submit" name="apply" value="0">{{ __('Preview calendar') }}</button>
                    @can('term-create')<button class="m3-button m3-button--filled" type="submit" name="apply" value="1">{{ __('Apply calendar template') }}</button>@endcan
                </form>
            @endif
            @if (($preview = session('term_preview')) && $preview['school_id'] === (int) $year->school_id && $preview['session_year_id'] === (int) $year->id)
                <h3>{{ __('Calendar preview') }}</h3>
                <p>{{ $preview['applied'] ? __('Terms added') : __('Missing terms') }}: {{ $preview['applied'] ? $preview['created_count'] : $preview['would_create_count'] }} · {{ __('Existing terms retained') }}: {{ $preview['skipped_count'] }}</p>
                <ul>@foreach ($preview['terms'] as $draft)<li>{{ $draft['name'] }}: {{ $draft['starts_on'] }} — {{ $draft['ends_on'] }} · {{ $grades[$draft['grade_from']] }} — {{ $grades[$draft['grade_to']] }}</li>@endforeach</ul>
            @endif
        </div></section>
    @else
        <p>{{ __('Create a school year before setting term dates.') }}</p>
        <a class="m3-button m3-button--outlined" href="{{ route('session-years.index') }}">{{ __('School years') }}</a>
    @endif
</div>
@endsection
