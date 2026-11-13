@extends('layouts.master')
@section('title', __('School terms'))
@section('content')
<div class="content-wrapper m3 page-content">
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
        @php
            $grades = collect(range(0, 12))->mapWithKeys(fn ($g) => [$g => __('Grade').' '.($g === 0 ? 'R' : $g)])->all();
        @endphp
        <nav class="panel-local-nav" aria-label="{{ __('Calendar tasks') }}">
            @foreach(['list'=>'Calendar entries','create'=>'Add term','configuration'=>'Class term settings','templates'=>'Calendar templates'] as $key=>$label)
                @if(!in_array($key,['create','configuration']) || auth()->user()->can($key==='create'?'term-create':'term-edit'))
                <a class="m3-button {{ $view===$key?'m3-button--tonal':'m3-button--text' }}" href="{{ route('term.index',['session_year_id'=>$year->id,'view'=>$key]) }}" @if($view===$key) aria-current="page" @endif>{{ __($label) }}</a>
                @endif
            @endforeach
        </nav>
        <p>{{ __('Optional lock and report dates prepare the calendar; assessment enforcement and report release are separate features.') }}</p>
        @if(in_array($view,['list','edit'],true))
        <h2>{{ __('Calendar entries') }}</h2>
        @forelse (collect($terms)->when($view==='edit', fn($rows)=>$rows->where('id',(int)request('term'))) as $row)
            <section class="m3 m3-card m3-card--outlined page-form-card" data-term-id="{{ $row['id'] }}"><div class="m3-card__content page-card__content">
                <h3>{{ $row['name'] }}</h3>
                @if ($row['needs_review'])<p role="status">{{ __('Imported term dates need review.') }}</p>@endif
                <p>{{ $row['starts_on'] }} — {{ $row['ends_on'] }} · {{ $grades[$row['grade_from']] }} — {{ $grades[$row['grade_to']] }}</p>
                <dl><dt>{{ __('Marks lock date (optional)') }}</dt><dd>{{ $row['marks_lock_on'] ?? __('Not set') }}</dd>
                <dt>{{ __('Report date (optional)') }}</dt><dd>{{ $row['reports_on'] ?? __('Not set') }}</dd></dl>
                @can('term-edit')
                    @if($view==='edit')
                    <h3>{{ __('Edit term') }}: {{ $row['name'] }}</h3>
                        <form method="POST" action="{{ route('term.update', $row['id']) }}">@csrf @method('PUT')
                            @include('term.fields', ['prefix' => 'term-'.$row['id']])
                            <button class="m3-button m3-button--filled" type="submit">{{ __('Save term') }}</button>
                        </form>
                    @else
                    <a class="m3-button m3-button--outlined" href="{{ route('term.index',['session_year_id'=>$year->id,'view'=>'edit','term'=>$row['id']]) }}">{{ __('Edit term') }}</a>
                    @endif
                @endcan
                @can('term-delete')
                    <form method="POST" action="{{ route('term.destroy', $row['id']) }}" data-confirm="{{ __('Remove this unused term? Existing records may prevent removal.') }}" data-confirm-danger>@csrf @method('DELETE')
                        <button class="m3-button m3-button--text" type="submit" aria-label="{{ __('Remove term') }} {{ $row['name'] }}">{{ __('Remove term') }}</button>
                    </form>
                @endcan
            </div></section>
        @empty
            <p role="status">{{ __('No terms yet. Add dates or preview a calendar template.') }}</p>
        @endforelse
        @endif
        @if($view==='configuration')
        @can('term-edit')
            @can('assign-class-subject')
                <section class="m3 m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">
                    <h2>{{ __('Class term settings') }}</h2>
                    <p>{{ __('Enable term-based subjects for a class in this year. Existing records are retained. Review each subject allocation after enabling; whole-year allocations are not automatically assigned to a term.') }}</p>
                    <form method="POST" action="{{ route('term.classes.enable') }}">@csrf
                        <input type="hidden" name="session_year_id" value="{{ $year->id }}">
                        <x-page.field id="term-class" name="class_id" type="select" :label="__('Class')" :options="$classes->mapWithKeys(fn ($class) => [$class->id => $class->name.' · '.$class->medium?->name.' · '.($class->getIncludeTermsForSession($year->id) ? __('Terms enabled') : __('Terms not enabled'))])->all()" required />
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Enable terms and review subjects') }}</button>
                    </form>
                </div></section>
            @endcan
        @endcan
        @endif
        @if(in_array($view,['create','templates'],true))
        @can('term-create')
            <section class="m3 m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">
                @if($view==='create')
                <h2>{{ __('Add term') }}</h2>
                <form method="POST" action="{{ route('term.store') }}">@csrf
                    @include('term.fields', ['prefix' => 'new-term', 'row' => []])
                    <button class="m3-button m3-button--filled" type="submit">{{ __('Add term') }}</button>
                </form>
                @else
                <h2>{{ __('Save this calendar as a template') }}</h2>
                <form method="POST" action="{{ route('term.templates.store') }}">@csrf
                    <input type="hidden" name="session_year_id" value="{{ $year->id }}">
                    <x-page.field id="template-name" name="name" :label="__('Template name')" required maxlength="120" />
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Save calendar template') }}</button>
                </form>
                @endif
            </div></section>
        @endcan
        @endif
        @if($view==='templates')
        <section class="m3 m3-card m3-card--outlined page-form-card"><div class="m3-card__content page-card__content">
            <h2>{{ __('School calendar templates') }}</h2>
            @php
                $preview = session('term_preview');
                $preview = is_array($preview) && $preview['school_id'] === (int) $year->school_id && $preview['session_year_id'] === (int) $year->id ? $preview : null;
            @endphp
            @if ($templates->isEmpty())<p>{{ __('No calendar templates saved yet.') }}</p>@else
                <p>{{ __('Preview first. Applying adds missing terms and never replaces existing dates.') }}</p>
                <form method="POST" action="{{ route('term.templates.apply') }}" data-calendar-template-review="{{ $preview && !$preview['applied'] ? $preview['template_id'] : '' }}">@csrf
                    <input type="hidden" name="session_year_id" value="{{ $year->id }}">
                    <x-page.field id="apply-template" name="template_id" type="select" :label="__('Calendar template')" :value="$preview['template_id'] ?? null" :options="$templates->pluck('name', 'id')->all()" required />
                    <button class="m3-button m3-button--outlined" type="submit" name="apply" value="0">{{ __('Preview calendar') }}</button>
                    @can('term-create')<button class="m3-button m3-button--filled" type="submit" name="apply" value="1" data-apply-reviewed-calendar @disabled(!$preview || $preview['applied'])>{{ __('Apply calendar template') }}</button>@endcan
                </form>
            @endif
            @if ($preview)
                <h3>{{ __('Calendar preview') }}</h3>
                <p>{{ $preview['applied'] ? __('Terms added') : __('Missing terms') }}: {{ $preview['applied'] ? $preview['created_count'] : $preview['would_create_count'] }} · {{ __('Existing terms retained') }}: {{ $preview['skipped_count'] }}</p>
                <ul>@foreach ($preview['terms'] as $draft)<li>{{ $draft['name'] }}: {{ $draft['starts_on'] }} — {{ $draft['ends_on'] }} · {{ $grades[$draft['grade_from']] }} — {{ $grades[$draft['grade_to']] }}</li>@endforeach</ul>
            @endif
        </div></section>
        @endif
    @else
        <p>{{ __('Create a school year before setting term dates.') }}</p>
        <a class="m3-button m3-button--outlined" href="{{ route('session-years.index') }}">{{ __('School years') }}</a>
    @endif
</div>
@endsection

@section('script')<script src="{{ asset('assets/js/custom/term-workspace.js') }}?v={{ filemtime(public_path('assets/js/custom/term-workspace.js')) }}" defer></script>@endsection
