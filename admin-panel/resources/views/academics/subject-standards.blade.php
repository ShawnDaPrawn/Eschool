@extends('layouts.master')
@section('title', __('Subject standards'))
@section('content')
<div class="content-wrapper">
    <x-page.header :title="__('Subject standards')" :subtitle="__('Choose a sourced curriculum reference without changing existing marks.')" />
    @if ($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    <section class="m3 m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">
        <form method="GET" action="{{ route('subject-standards.index') }}">
            <x-page.field id="standards-query" name="q" :label="__('Search subject names or codes')" :value="request('q')" maxlength="100" />
            <x-page.field id="standards-framework" name="framework" type="select" :label="__('Framework')" :value="request('framework')" :options="['' => __('All frameworks'), 'CAPS' => 'CAPS', 'IEB' => 'IEB']" />
            <x-page.field id="standards-grade" name="grade" type="select" :label="__('Grade')" :value="request('grade')" :options="['' => __('All grades')] + collect(range(0, 12))->mapWithKeys(fn ($g) => [$g => $g === 0 ? 'R' : (string) $g])->all()" />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Find standards') }}</button>
        </form>
    </div></section>
    <h2>{{ __('Published catalogue') }}</h2>
    @forelse ($catalogue as $standard)
        <section class="m3 m3-card m3-card--outlined page-form-card" data-standard-id="{{ $standard->id }}"><div class="m3-card__content page-card__content">
            <h3>{{ $standard->name }}</h3>
            <p>{{ $standard->framework }} · {{ $standard->code }} · {{ $standard->phase }} · {{ __('Grade') }} {{ $standard->grade_from === 0 ? 'R' : $standard->grade_from }}–{{ $standard->grade_to }}</p>
            <p>{{ __('Version') }}: {{ $standard->version }} · {{ __('Effective date') }}: {{ $standard->effective_on->toDateString() }}</p>
            <a href="{{ $standard->source_url }}" target="_blank" rel="noopener noreferrer">{{ __('View source') }}</a>
            @if ($standard->component_weights)
                <dl>@foreach ($standard->component_weights as $component => $weight)<dt>{{ strtoupper($component) }}</dt><dd>{{ number_format($weight / 100, 2) }}%</dd>@endforeach</dl>
            @else<p>{{ __('Assessment weights are not supplied by this reference.') }}</p>@endif
            <p>{{ __('Mapping a standard does not change assessment plans or existing marks.') }}</p>
            @can('subject-create')
                <details><summary>{{ __('Create school subject') }}: {{ $standard->name }}</summary>
                    <form method="POST" action="{{ route('subject-standards.store') }}">@csrf
                        <input type="hidden" name="standard_id" value="{{ $standard->id }}">
                        <x-page.field :id="'standard-medium-'.$standard->id" name="medium_id" type="select" :label="__('Medium')" :options="$mediums->pluck('name', 'id')->all()" required />
                        <x-page.field :id="'standard-type-'.$standard->id" name="type" type="select" :label="__('Subject type')" :options="['Theory' => __('Theory'), 'Practical' => __('Practical')]" required />
                        <x-page.field :id="'standard-colour-'.$standard->id" name="bg_color" type="color" :label="__('Subject colour')" required />
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Create school subject') }}</button>
                    </form>
                </details>
            @endcan
        </div></section>
    @empty
        <p role="status">{{ __('No published standards match. Change the filters or ask your platform administrator to import a reviewed catalogue.') }}</p>
    @endforelse
    <nav aria-label="{{ __('Catalogue pages') }}">
        @if ($catalogue->previousPageUrl())<a class="m3-button m3-button--text" href="{{ $catalogue->previousPageUrl() }}">{{ __('Previous') }}</a>@endif
        @if ($catalogue->nextPageUrl())<a class="m3-button m3-button--text" href="{{ $catalogue->nextPageUrl() }}">{{ __('Next') }}</a>@endif
    </nav>
    <h2>{{ __('School subject mappings') }}</h2>
    @foreach ($subjects as $subject)
        <section class="m3 m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">
            <h3>{{ $subject->name }}</h3>
            <p>{{ $subject->standard?->framework }} {{ $subject->standard?->code ?? __('Not mapped') }} {{ $subject->standard?->version }}</p>
            @can('subject-edit')
                <form method="POST" action="{{ route('subject-standards.update', $subject->id) }}">@csrf @method('PUT')
                    @php($options = ['' => __('No reference')] + $catalogue->getCollection()->mapWithKeys(fn ($s) => [$s->id => $s->framework.' · '.$s->code.' · '.$s->name.' · '.$s->version])->all() + ($subject->standard ? [$subject->standard_id => $subject->standard->framework.' · '.$subject->standard->code.' · '.$subject->standard->name.' · '.$subject->standard->version] : []))
                    <label class="m3-text-field m3-text-field--select" for="subject-standard-{{ $subject->id }}">
                        <select class="m3-text-field__input" id="subject-standard-{{ $subject->id }}" name="standard_id">
                            @foreach ($options as $id => $label)<option value="{{ $id }}" @selected((string) $id === (string) $subject->standard_id)>{{ $label }}</option>@endforeach
                        </select>
                        <span class="m3-text-field__label">{{ __('Curriculum reference') }}</span>
                    </label>
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Save subject reference') }}</button>
                </form>
            @endcan
        </div></section>
    @endforeach
</div>
@endsection
