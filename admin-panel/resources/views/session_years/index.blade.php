@extends('layouts.master')
@section('title', __('School years'))
@section('content')
<div class="content-wrapper m3 page-content">
<x-page.header :title="__('School years')" :subtitle="__('Viewing a year changes your context. Activation changes the current year for the whole school.')" :primary-label="auth()->user()->can('session-year-create') ? ($current ? __('Prepare next school year') : __('Prepare first school year')) : null" :primary-href="$current ? route('new-year.index') : route('session-years.index',['view'=>'create'])" />
<p class="m3-banner m3-banner--info">{{ __('School current year') }}: <strong>{{ $current?->name ?? __('Not configured') }}</strong></p>
@if(!in_array($view,['list','create']))
<nav class="panel-local-nav" aria-label="{{ __('School year tasks') }}"><a class="m3-button m3-button--text" href="{{ route('session-years.index') }}">{{ __('All school years') }}</a><a class="m3-button m3-button--text" href="{{ route('session-years.index',['year'=>$selectedYear->id,'view'=>'calendar']) }}">{{ __('Calendar and terms') }}</a>
@can('session-year-edit')<a class="m3-button m3-button--text" href="{{ route('session-years.index',['year'=>$selectedYear->id,'view'=>'edit']) }}">{{ __('Edit dates') }}</a>@endcan
</nav>
@endif
@if($view === 'create')
<x-page.form-card id="school-year-create" :action="route('session-years.store')" :title="__('Prepare first school year')" :redirect="route('session-years.index')" :cancel-href="route('session-years.index')" :write="false">
<x-page.field name="name" :label="__('School year name')" required maxlength="255" />
<x-page.field name="start_date" type="date" :label="__('Start date')" required />
<x-page.field name="end_date" type="date" :label="__('End date')" required />
<p>{{ __('Preparation creates the calendar record. Review global activation separately after saving.') }}</p>
</x-page.form-card>
@elseif($view === 'activate')
<x-page.form-card id="school-year-initial-activation" :action="url('session-years/'.$selectedYear->id.'/make-current')" :title="__('Activate first school year')" :submit-label="__('Activate for the whole school')" :redirect="route('session-years.index')" :cancel-href="route('session-years.index')" :write="false" :draft="false">
<input type="hidden" name="source_year_id" value="0">
<p>{{ $selectedYear->name }} · {{ $selectedYear->start_date }} – {{ $selectedYear->end_date }}</p>
<p>{{ __('This establishes the current year for the whole school. Future changes use the global preparation and carry-forward workflow.') }}</p>
</x-page.form-card>
@elseif($view === 'edit')
<x-page.form-card id="school-year-edit" :action="route('session-years.update',$selectedYear->id)" method="PUT" :title="__('Edit prepared year dates')" :redirect="route('session-years.index',['year'=>$selectedYear->id,'view'=>'calendar'])" :cancel-href="route('session-years.index')" :write="false">
<input type="hidden" name="id" value="{{ $selectedYear->id }}">
<x-page.field name="name" :label="__('Name')" :value="$selectedYear->name" required maxlength="255" />
<div class="m3-grid"><div class="m3-col-12 m3-col-expanded-6"><x-page.field name="start_date" type="date" :label="__('Start date')" :value="$selectedYear->start_date" required /></div><div class="m3-col-12 m3-col-expanded-6"><x-page.field name="end_date" type="date" :label="__('End date')" :value="$selectedYear->end_date" required /></div></div>
<p>{{ __('Editing dates does not activate this school year. Review the terms before changing dates.') }}</p>
</x-page.form-card>
@elseif($view === 'calendar')
<section class="page-section"><h2>{{ $selectedYear->name }}</h2><p>{{ $selectedYear->start_date }} – {{ $selectedYear->end_date }}</p>
<div class="page-table__scroll" tabindex="0" role="region" aria-label="{{ __('School terms') }}"><table class="page-table__table"><thead><tr><th>{{ __('Term') }}</th><th>{{ __('Start date') }}</th><th>{{ __('End date') }}</th></tr></thead><tbody>
@forelse($terms as $term)<tr><td>{{ $term->name }}</td><td>{{ $term->start_date }}</td><td>{{ $term->end_date }}</td></tr>@empty<tr><td colspan="3">{{ __('No terms are configured for this school year.') }}</td></tr>@endforelse
</tbody></table></div>
@canany(['session-year-create','session-year-edit'])
@if($current && $selectedYear->start_date > $current->start_date)<a class="m3-button m3-button--filled" href="{{ route('new-year.index',['year'=>$selectedYear->id]) }}">{{ __('Review global activation') }}</a>@endif
@endcanany
</section>
@elseif($view === 'cleanup')
@include('session_years.delete-clear-modal')
@else
<form method="GET" class="page-toolbar"><x-page.field name="search" type="search" :label="__('Search school years')" :value="request('search')" maxlength="100" /><button class="m3-button m3-button--outlined" type="submit">{{ __('Search') }}</button></form>
<div class="page-table__scroll" tabindex="0" role="region" aria-label="{{ __('School years') }}"><table class="page-table__table"><thead><tr><th>{{ __('School year') }}</th><th>{{ __('First day') }}</th><th>{{ __('Last day') }}</th><th>{{ __('State') }}</th><th>{{ __('Actions') }}</th></tr></thead><tbody>
@forelse($rows as $year)
<tr><th scope="row">{{ $year->name }}</th><td>{{ $year->start_date }}</td><td>{{ $year->end_date }}</td><td>{{ (int)$year->id === (int)$current?->id ? __('Current') : ($year->start_date > $current?->start_date ? __('Prepared') : __('Historical')) }}</td><td><div class="page-form-actions"><a class="m3-button m3-button--text" href="{{ route('session-years.index',['year'=>$year->id,'view'=>'calendar']) }}">{{ __('View calendar') }}</a>
@can('session-year-edit')<a class="m3-button m3-button--text" href="{{ route('session-years.index',['year'=>$year->id,'view'=>'edit']) }}">{{ __('Edit dates') }}</a>
@if(!$current)<a class="m3-button m3-button--outlined" href="{{ route('session-years.index',['year'=>$year->id,'view'=>'activate']) }}">{{ __('Review global activation') }}</a>@endif
@endcan
@can('session-year-delete')
@if((int)$year->id !== (int)$current?->id)<a class="m3-button m3-button--text" href="{{ route('session-years.index',['year'=>$year->id,'view'=>'cleanup']) }}">{{ __('Review cleanup') }}</a>@endif
@endcan
</div></td></tr>
@empty<tr><td colspan="5">{{ __('No school years match this search.') }}</td></tr>@endforelse
</tbody></table></div>{{ $rows->links() }}
@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/school-year-workspace.js') }}" defer></script>@endsection
