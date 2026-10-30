@extends('layouts.master')
@section('title', __('Guardians'))
@section('content')
@php
    $relationshipOptions = collect($relationships)->mapWithKeys(fn ($value) => [$value => __('relationship_'.$value)])->all();
    $active = collect($guardians)->where('status', 'active');
    $ended = collect($guardians)->where('status', '!=', 'active');
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Guardians')" :subtitle="$learner->user?->full_name" />
    <p>{{ __('A learner can have any number of guardians. At least one must be able to approve the learner’s access and school decisions. Removing a guardian takes effect at once.') }}</p>
    @foreach (['guardian', 'email', 'reason'] as $key)@error($key)<p role="alert">{{ $message }}</p>@enderror @endforeach

    <section class="page-section" aria-labelledby="current-guardians">
        <h2 id="current-guardians">{{ __('Current guardians') }}</h2>
        @forelse ($active as $guardian)
            <article class="m3-card m3-card--outlined"><div class="m3-card__content">
                <h3>{{ $guardian['name'] }}</h3>
                <p>{{ __('relationship_'.$guardian['relationship']) }} · {{ $guardian['email'] }}
                    · {{ $guardian['can_approve'] ? __('Can approve') : __('Cannot approve') }} · {{ $guardian['can_see_fees'] ? __('Sees fees') : __('Does not see fees') }}</p>
                <form method="POST" action="{{ route('learner-guardians.update', [$learner->id, $guardian['id']]) }}" aria-label="{{ __('Change :name', ['name' => $guardian['name']]) }}">
                    @csrf
                    @method('PUT')
                    <x-page.field name="relationship" :id="'relationship-'.$guardian['id']" type="select" :label="__('Relationship')" :value="$guardian['relationship']" :options="$relationshipOptions" :restore-old-input="false" required />
                    <label><input type="checkbox" name="can_approve" value="1" @checked($guardian['can_approve'])> {{ __('Can approve access and school decisions') }}</label>
                    <label><input type="checkbox" name="can_see_fees" value="1" @checked($guardian['can_see_fees'])> {{ __('Can see fees') }}</label>
                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Save changes') }}</button>
                </form>
                <form method="POST" action="{{ route('learner-guardians.end', [$learner->id, $guardian['id']]) }}" aria-label="{{ __('Remove :name', ['name' => $guardian['name']]) }}">
                    @csrf
                    <x-page.field name="reason" :id="'reason-'.$guardian['id']" :label="__('Reason for removing (kept in the audit log)')" :restore-old-input="false" minlength="5" maxlength="500" required />
                    <button class="m3-button m3-button--text" type="submit">{{ __('Remove guardian') }}</button>
                </form>
            </div></article>
        @empty
            <x-page.empty-state :title="__('No guardians yet')" :message="__('Add the learner’s first guardian below.')" />
        @endforelse
    </section>

    <section class="m3-card m3-card--outlined page-section" aria-labelledby="add-guardian"><div class="m3-card__content">
        <h2 id="add-guardian">{{ __('Add a guardian') }}</h2>
        <p>{{ __('If the email address already has a NavigateEd account, that person is linked; otherwise an account is created and they are emailed an invitation.') }}</p>
        <form method="POST" action="{{ route('learner-guardians.store', $learner->id) }}" aria-label="{{ __('Add a guardian') }}">
            @csrf
            <div>
                <x-page.field name="email" id="guardian-email" type="email" :label="__('Email')" maxlength="191" required />
                <x-page.field name="first_name" id="guardian-first-name" :label="__('First name')" maxlength="128" required />
                <x-page.field name="last_name" id="guardian-last-name" :label="__('Last name')" maxlength="128" required />
                <x-page.field name="mobile" id="guardian-mobile" type="tel" :label="__('Mobile (optional)')" maxlength="32" />
                <x-page.field name="relationship" id="guardian-relationship" type="select" :label="__('Relationship')" :options="$relationshipOptions" required />
            </div>
            <label><input type="checkbox" name="can_approve" value="1" checked> {{ __('Can approve access and school decisions') }}</label>
            <label><input type="checkbox" name="can_see_fees" value="1" checked> {{ __('Can see fees') }}</label>
            <button class="m3-button m3-button--filled" type="submit">{{ __('Add guardian') }}</button>
        </form>
    </div></section>

    @if ($ended->isNotEmpty())
        <section class="page-section" aria-labelledby="past-guardians">
            <h2 id="past-guardians">{{ __('Past guardians') }}</h2>
            <ul>@foreach ($ended as $guardian)<li>{{ $guardian['name'] }} · {{ __('relationship_'.$guardian['relationship']) }}@if ($guardian['ended_at']) · {{ __('Removed on :date', ['date' => $guardian['ended_at']->toDateString()]) }}@endif</li>@endforeach</ul>
        </section>
    @endif
</div>
@endsection
