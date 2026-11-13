@extends('layouts.master')
@section('title', __('Invite an alumnus or volunteer'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Invite an alumnus or volunteer')" :subtitle="__('Create a dedicated pool account. This does not create school staff access or give consent on the person’s behalf.')" />
    @include('people.pool.navigation')
    <form method="GET" action="{{ route('people-pool-panel.create') }}" class="panel-form-grid" role="search">
        <x-page.field name="learner_search" :label="__('Find an adult former learner')" :value="request('learner_search')" maxlength="100" :restore-old-input="false" />
        <button class="m3-button m3-button--outlined" type="submit">{{ __('Find former learners') }}</button>
    </form>
    <div class="workspace-layout"><section class="workspace-main m3-card m3-card--outlined"><div class="m3-card__content">
    <form method="POST" action="{{ route('people-pool-panel.invite') }}" class="panel-form-grid" autocomplete="off">@csrf
        <input type="hidden" name="operation_id" value="{{ old('operation_id', (string) \Illuminate\Support\Str::uuid()) }}">
        <x-page.field name="kind" type="select" :label="__('Member type')" :options="['alumnus' => __('Alumnus'), 'volunteer' => __('Volunteer')]" :value="old('kind', 'alumnus')" required />
        <x-page.field name="learner_id" type="select" :label="__('Former learner')" :options="$formerLearners" :placeholder="__('Choose a former learner')" :help="__('Required for an alumnus; leave empty for a volunteer. The school verifies former learner status and adulthood.')" />
        <x-page.field name="first_name" :label="__('First name')" maxlength="100" required />
        <x-page.field name="last_name" :label="__('Last name')" maxlength="100" required />
        <x-page.field name="email" type="email" :label="__('Email address')" maxlength="191" required />
        <x-page.field name="initial_password" type="password" :label="__('Initial password')" :restore-old-input="false" autocomplete="new-password" maxlength="72" required :help="__('Share privately. The person must change this password at first sign-in; it is never saved as a form draft.')" />
        <x-page.field name="occupation" :label="__('Occupation')" maxlength="191" />
        <x-page.field name="organisation" :label="__('Organisation')" maxlength="191" />
        <x-page.field name="offers_career_talk" type="select" :label="__('Offers career talks')" :options="[0 => __('No'), 1 => __('Yes')]" :value="old('offers_career_talk', 0)" required />
        <x-page.field name="offers_job_shadow" type="select" :label="__('Offers group job shadows')" :options="[0 => __('No'), 1 => __('Yes')]" :value="old('offers_job_shadow', 0)" required />
        <div class="panel-actions"><a class="m3-button m3-button--outlined" href="{{ route('people-pool-panel.index') }}">{{ __('Cancel') }}</a><button class="m3-button m3-button--filled" type="submit">{{ __('Create invitation') }}</button></div>
    </form></div></section>
    <aside class="workspace-context m3-card m3-card--filled"><div class="m3-card__content"><h2>{{ __('Before inviting') }}</h2><p>{{ __('At least one session type must be offered. An alumnus must be an adult former learner of this school.') }}</p><p>{{ __('An invitation is not consent or clearance. Only the member can accept the current consent wording. Vetting and group supervision are checked again when booking.') }}</p></div></aside></div>
</div>
@endsection
