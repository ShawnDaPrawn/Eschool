@extends('layouts.master')
@section('title',__('Configure intake'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/admissions.css') }}">@endsection
@section('content')
<div class="content-wrapper admissions-workbench"><x-page.header :title="__('Configure intake')" /><x-m3.button variant="text" :href="route('admissions-panel.index')">{{ __('Back to admissions') }}</x-m3.button>
<form method="post" action="{{ $intake?route('admissions-panel.configure',['intake'=>$intake->id]):route('admissions-panel.create') }}" class="admissions-card">@include('admissions.panel.hidden',['revision'=>$intake?->revision??0])
<x-m3.text-field name="name" :label="__('Intake name')" :value="$intake?->name" maxlength="160" required />
<x-m3.select name="session_year_id" :label="__('School year')" :options="$years->pluck('name','id')" :value="$intake?->session_year_id" required />
<x-m3.select name="state" :label="__('Status')" :options="['draft'=>__('Draft'),'open'=>__('Open'),'closed'=>__('Closed')]" :value="$intake?->state??'draft'" required />
@foreach(['opens_at'=>'Applications open','closes_at'=>'Applications close'] as $key=>$label)<x-m3.text-field :name="$key" :label="__($label)" :hint="app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone" type="datetime-local" :value="$intake?->getAttribute($key)?->setTimezone(app(\App\Services\Platform\Tenancy\CurrentSchool::class)->school()->timezone)->format('Y-m-d\TH:i')" required />@endforeach
<x-m3.text-field name="application_fee_cents" :label="__('Application fee in cents')" type="number" min="0" step="1" :value="$intake?->application_fee_cents??0" required />
<p>{{ __('Until online application payments are available, the office records the exact fee acknowledgement and receipt reference.') }}</p>
<x-m3.text-field name="keep_for_days" :label="__('Retention days for incomplete and unsuccessful applications')" type="number" min="1" max="365" :value="$intake?->keep_for_days??90" required />
<x-m3.text-field name="document_categories" :label="__('Required document categories, separated by commas')" :value="implode(', ',$intake?->required_documents??[])" placeholder="birth_certificate, previous_report" maxlength="800" />
<p>{{ __('Use short category names with lowercase letters and underscores. Documents are encrypted privately and must pass scanning before submission.') }}</p>
<fieldset><legend>{{ __('Profile fields on the application') }}</legend><p>{{ __('Include the learner first name, last name and date of birth. Only fields available to applicants and the school office can be selected. Keep the form and fee unchanged once a family has started an application.') }}</p>
@foreach($fields as $field)<x-m3.checkbox name="field_ids[]" :value="$field->id" :checked="in_array((int)$field->id,$intake?->field_ids??[],true)" :label="__(ucfirst($field->profile_type)).' · '.$field->category->label.' · '.$field->label.($field->sensitive?' ('.__('Sensitive').')':'')" />@endforeach</fieldset>
<fieldset><legend>{{ __('Grade capacities') }}</legend><p>{{ __('Set available places for each grade. Zero places allows applications to wait for capacity. Capacity cannot fall below reserved or enrolled places.') }}</p><div class="admissions-grid">@foreach($grades as $grade)<input type="hidden" name="grades[{{ $loop->index }}][grade_level_id]" value="{{ $grade->id }}"><x-m3.text-field :name="'grades['.$loop->index.'][places]'" :label="$grade->name" type="number" min="0" max="10000" :value="$capacities[$grade->id]??0" required />@endforeach</div></fieldset><x-m3.button type="submit">{{ __('Save intake') }}</x-m3.button></form></div>
@endsection
