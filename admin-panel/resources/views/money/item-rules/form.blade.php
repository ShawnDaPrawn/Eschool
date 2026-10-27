@extends('layouts.master')
@section('title', __('Item eligibility'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 fee-plan-workbench">
<x-page.header :title="__('Item eligibility').' · '.$item->name" />
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
<x-m3.button variant="text" :href="route('fee-items.index')">{{ __('Back to fee items') }}</x-m3.button>
<p>{{ __('Automatic charges use the dated enrolment and membership on the charge date. Review each batch before posting. Family items charge once when any assigned learner qualifies, with the whole family allocation shown.') }}</p>
<p>{{ __('Activity groups can represent clubs, teams or transport routes. Subject membership uses dated teaching-group enrolments. Editing a rule never changes a posted invoice.') }}</p>
@if(!$canWrite)<p>{{ __('Item eligibility is read-only while fees are disabled.') }}</p>@endif
<form method="post" action="{{ route('fee-items.rules-save',['item'=>$item->id]) }}" data-year-safe>
@include('money.fee-plans.hidden',['revision'=>$rule?->revision??0])
<fieldset @disabled(!$canWrite)><legend>{{ __('Automatic eligibility') }}</legend>
<x-m3.select name="mode" :label="__('Item charging mode')" :options="['manual'=>__('Manual charges only'),'compulsory'=>__('Compulsory by rule'),'membership'=>__('Membership-driven')]" :value="$rule?->mode??'manual'" required />
<x-m3.select name="target_choice" :label="__('Eligible enrolments')" :options="$targets" :value="($rule?->target_scope??'all').':'.($rule?->target_id??0)" required />
@php($selectedSource=$rule?->activity_group_id?'activity_group:'.$rule->activity_group_id:($rule?->teaching_group_id?'teaching_group:'.$rule->teaching_group_id:($rule?->subject_id?'subject:'.$rule->subject_id:'')))
<x-m3.select name="membership_choice" :label="__('Membership source')" :options="$sources" :value="$selectedSource" />
<p>{{ __('Select one source for membership-driven items. Manual and compulsory items use no membership source. Manual items use all enrolments and no additional rules.') }}</p>
<details><summary>{{ __('Additional matching rules') }}</summary>
<p>{{ __('Every rule must match. Each rule accepts any listed value. Campus and boarding can use eligible learner profile fields; hidden or sensitive fields are unavailable.') }}</p>
@php($ruleOptions=[''=>__('No additional rule'),'phase'=>__('Grade phase')])
@foreach($ruleFields as $field)@php($ruleOptions['profile:'.$field->id]=$field->label)@endforeach
@for($i=0;$i<max(4,count($rule?->applies_to??[]));$i++)
@php($condition=($rule?->applies_to??[])[$i]??null)
<fieldset><legend>{{ __('Matching rule') }} {{ $i+1 }}</legend>
<x-m3.select :name="'rule_rows['.$i.'][field]'" :label="__('Rule field')" :options="$ruleOptions" :value="$condition?($condition['field']==='phase'?'phase':'profile:'.$condition['definition_id']):''" />
<x-m3.text-field type="textarea" :name="'rule_rows['.$i.'][values]'" :label="__('Matching values, one per line')" :hint="__('Use Foundation, Intermediate, Senior or FET for phase; Yes or No for boolean fields.')" :value="$condition?implode(PHP_EOL,array_map(fn($v)=>is_bool($v)?($v?__('Yes'):__('No')):$v,$condition['values'])):''" rows="2" maxlength="16000" />
</fieldset>
@endfor
</details>
<x-m3.button type="submit">{{ __('Save item eligibility') }}</x-m3.button>
</fieldset></form>
<x-m3.button variant="outlined" :href="route('fee-item-batches.new',['fee_item_id'=>$item->id])">{{ __('Prepare automatic item batch') }}</x-m3.button>
</div>
@endsection
