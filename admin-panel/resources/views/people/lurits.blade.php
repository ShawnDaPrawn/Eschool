@extends('layouts.master')
@section('title', __('LURITS numbers'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('LURITS numbers')" :subtitle="__('Each learner\'s LURITS number is their main identifier and follows them to any school. Record the numbers still missing, for example from the SA-SAMS learner list, or correct a wrong one.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if (session('warning'))<p role="alert">{{ session('warning') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="page-section" aria-labelledby="lurits-summary">
        <h2 id="lurits-summary">{{ __(':with of :learners learners have a LURITS number.', $summary) }}</h2>
        @if ($summary['without'] > 0)
            <p>{{ trans_choice(':count learner still uses a temporary NavigateEd number.|:count learners still use a temporary NavigateEd number.', $summary['without'], ['count' => $summary['without']]) }}</p>
        @endif
    </section>

    <section class="m3-card m3-card--outlined page-section" aria-labelledby="lurits-record"><div class="m3-card__content">
        <h2 id="lurits-record">{{ __('Record or correct a number') }}</h2>
        <form method="POST" action="{{ route('lurits-office.store') }}" aria-label="{{ __('Record or correct a number') }}">
            @csrf
            <x-page.field name="admission_no" id="lurits-admission" :label="__('Admission number')" maxlength="64" required />
            <x-page.field name="lurits" id="lurits-number" :label="__('LURITS number')" maxlength="32" inputmode="numeric" autocomplete="off" required />
            <button class="m3-button m3-button--filled" type="submit">{{ __('Save the number') }}</button>
        </form>
    </div></section>

    <section class="page-section" aria-labelledby="lurits-missing">
        <h2 id="lurits-missing">{{ __('Learners without a LURITS number') }}</h2>
        @if ($missing->isEmpty())
            <x-page.empty-state :title="__('Every learner has a LURITS number')" :message="__('Nothing to do here.')" />
        @else
            <div class="page-table__scroll" role="region" aria-label="{{ __('Learners without a LURITS number') }}" tabindex="0"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('Learners without a LURITS number') }}</caption>
                <thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Admission number') }}</th><th scope="col">{{ __('Homeroom') }}</th></tr></thead>
                <tbody>
                @foreach ($missing as $learner)
                    <tr>
                        <th scope="row">{{ trim(($learner->user?->first_name ?? '').' '.($learner->user?->last_name ?? '')) }}</th>
                        <td>{{ $learner->admission_no }}</td>
                        <td>{{ trim(($learner->class_section?->class?->name ?? '').' '.($learner->class_section?->section?->name ?? '')) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $missing->links() }}
        @endif
    </section>

    @if ($claims !== [])
    <section class="page-section" aria-labelledby="lurits-claims">
        <h2 id="lurits-claims">{{ __('Numbers waiting for Navigate Ed support') }}</h2>
        <p>{{ __('These numbers are already registered to another learner, so they were not recorded. Navigate Ed support will check each one with you; if the learner is joining from another school, start a transfer request.') }}</p>
        <ul>
            @foreach ($claims as $claim)
                <li>{{ __(':learner (:number), :date', ['learner' => $claim['learner'], 'number' => $claim['admission_no'], 'date' => $claim['raised_at']]) }}</li>
            @endforeach
        </ul>
    </section>
    @endif
</div>
@endsection
