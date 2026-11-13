@extends('layouts.master')
@section('title', __('Release sponsored place'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/fee-plans.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content fee-plan-workbench"><x-page.header :title="__('Release sponsored place')" :subtitle="$record['sponsor_name'].' · '.$record['reference']" />
<x-m3.button variant="text" :href="route('sponsored-places.show',['subscription'=>$record['id']])">{{ __('Keep current assignment') }}</x-m3.button>
<x-page.form-card id="sponsored-release" :action="route('sponsored-places.remove',['subscription'=>$record['id'],'learner'=>$member['learner_id']])" :title="$member['name']" :submit-label="__('Release this learner’s place')" :draft="false" :cancel-href="route('sponsored-places.show',['subscription'=>$record['id']])">
@include('money.business.hidden')<p>{{ __('This removes the learner from this sponsorship. It does not cancel other learning plans or delete the learner record.') }}</p>
<x-m3.checkbox name="confirmed" value="1" :label="__('I reviewed the selected learner and sponsorship.')" required />
</x-page.form-card></div>
@endsection
