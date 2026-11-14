@extends('layouts.master')
@section('title', __('Attendance configuration'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Attendance configuration')" :subtitle="__('Configure teaching days and absence alerts separately from capturing attendance.')">
        <x-slot:actions><a class="m3-button m3-button--outlined" href="{{ route('academic-registers.index') }}">{{ __('Back to registers') }}</a></x-slot:actions>
    </x-page.header>
    <section class="m3-card m3-card--outlined"><div class="m3-card__content">
        <h2>{{ __('Absence alert settings') }}</h2>
        <p>{{ __('Cut-off uses the school timezone. Approved leave suppresses absence alerts.') }}</p>
        <form method="POST" action="{{ route('academic-registers.settings') }}" aria-label="{{ __('Save absence settings') }}">@csrf
            <x-page.field name="absence_cutoff" type="time" :label="__('Absence alert cut-off')" :value="$choices['settings']['absence_cutoff']" required />
            <fieldset><legend>{{ __('Teaching days') }}</legend>
                <div class="m3-row m3-gap-4">
                    @foreach([1 => __('Monday'), 2 => __('Tuesday'), 3 => __('Wednesday'), 4 => __('Thursday'), 5 => __('Friday'), 6 => __('Saturday'), 7 => __('Sunday')] as $number => $day)
                        <label class="m3-checkbox"><input class="m3-checkbox__input" type="checkbox" name="teaching_days[]" value="{{ $number }}" @checked(in_array($number, old('teaching_days', $choices['settings']['teaching_days']), false))><span class="m3-checkbox__control"></span><span>{{ $day }}</span></label>
                    @endforeach
                </div>
            </fieldset>
            <div class="page-form-actions"><button type="submit" class="m3-button m3-button--filled">{{ __('Save absence settings') }}</button></div>
        </form>
    </div></section>
</div>
@endsection
