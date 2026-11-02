@extends('layouts.master')
@section('title', __('Portfolio'))
@section('content')
@php($byArea = collect($items)->groupBy('development_area_id'))
<div class="content-wrapper m3 page-content">
    <x-page.header :title="$learner['name']" :subtitle="__('Portfolio').' · '.$section->full_name" />
    <p><a href="{{ route('early-years.class', ['section' => $section->id]) }}">{{ __('Back to the class') }}</a></p>
    @include('early-years.messages')

    @if ($can_write)
    <section class="m3-card m3-card--outlined page-section" aria-labelledby="observation-new"><div class="m3-card__content">
        <h2 id="observation-new">{{ __('Add an observation') }}</h2>
        <form method="POST" action="{{ route('early-years.observations.store', ['learner' => $learner['id']]) }}" enctype="multipart/form-data" aria-label="{{ __('Add an observation') }}">
            @csrf
            <input type="hidden" name="operation_id" value="{{ $operationId }}">
            <x-page.field id="observation-area" name="development_area_id" type="select" :label="__('Development area')" :options="$areas->where('active', true)->pluck('name', 'id')->all()" :restore-old-input="false" required />
            <x-page.field id="observation-day" name="observed_on" type="date" :label="__('Day')" :value="$today" :restore-old-input="false" max="{{ $today }}" required />
            <x-page.field id="observation-note" name="note" type="textarea" :label="__('What you saw')" :restore-old-input="false" maxlength="2000" :help="__('Describe what the child did and said. A note, a photograph or both.')" />
            @if ($photos && $consent_available)
                <x-page.field id="observation-photo" name="photo" type="file" :label="__('Photograph')" accept="image/jpeg,image/png" :restore-old-input="false"
                    :help="__('JPEG or PNG up to 4 MB. The family is asked to approve it in the private photo gallery; it shows here only once they have.')" />
            @elseif ($photos)
                <p>{{ __('Nobody can give photo consent for this child yet, so you can record notes only.') }}</p>
            @else
                <p>{{ __('Photographs need the private photo gallery to be switched on. You can record notes.') }}</p>
            @endif
            <button type="submit" class="m3-button m3-button--filled">{{ __('Save observation') }}</button>
        </form>
    </div></section>
    @endif

    @if ($items === [])
        <x-page.empty-state :title="__('No observations yet')" :message="__('Observations build this child\'s portfolio over the year.')" />
    @endif
    @foreach ($areas as $area)
        @if ($byArea->has($area->id))
        <section class="page-section" aria-labelledby="area-{{ $area->id }}">
            <h2 id="area-{{ $area->id }}">{{ $area->name }}</h2>
            @foreach ($byArea[$area->id] as $item)
                <article class="m3-card m3-card--outlined"><div class="m3-card__content">
                    <p>{{ $item['observed_on'] }} · {{ $item['recorded_by'] }}</p>
                    @if ($item['note'] !== null)<p>{{ $item['note'] }}</p>@endif
                    @if ($item['photo'] === 'approved')
                        <a href="{{ route('early-years.observations.photo', ['observation' => $item['id']]) }}"><img src="{{ route('early-years.observations.photo', ['observation' => $item['id'], 'thumbnail' => 1]) }}" alt="{{ __('Observation photograph') }}" width="360" height="270" loading="lazy"></a>
                    @elseif ($item['photo'] === 'awaiting')
                        <p>{{ __('Photograph waiting for the family\'s approval.') }}</p>
                    @elseif ($item['photo'] === 'declined')
                        <p>{{ __('The photograph is not available: the family did not approve it or removed it.') }}</p>
                    @elseif ($item['photo'] === 'unavailable')
                        <p>{{ __('The photograph is hidden while the private photo gallery is switched off.') }}</p>
                    @endif
                    @if ($item['concern'] !== null)
                        <p>{{ __('Early concern referred on :date by :name.', ['date' => $item['concern']['raised_on'], 'name' => $item['concern']['raised_by']]) }}</p>
                    @endif
                    @if ($can_write && $item['concern'] === null)
                        <details>
                            <summary>{{ __('Raise an early concern') }}</summary>
                            @if ($concern_owners === [])
                                <p>{{ __('A concern goes to a grade head as an intervention. Early warning must be switched on and a grade head appointed for this grade.') }}</p>
                            @else
                            <form method="POST" action="{{ route('early-years.observations.concern', ['observation' => $item['id']]) }}" aria-label="{{ __('Raise an early concern') }}">
                                @csrf
                                <p>{{ __('Describe what you saw and heard, and why it worries you. Do not name a condition or a diagnosis.') }}</p>
                                <x-page.field :id="'concern-text-'.$item['id']" name="concern" type="textarea" :label="__('What worries you')" :restore-old-input="false" minlength="10" maxlength="2000" required />
                                <x-page.field :id="'concern-owner-'.$item['id']" name="owner_id" type="select" :label="__('Refer to')" :options="array_column($concern_owners, 'name', 'id')" :restore-old-input="false" required />
                                <x-page.field :id="'concern-review-'.$item['id']" name="review_on" type="date" :label="__('Review by')" :value="\Illuminate\Support\Carbon::parse($today)->addWeeks(2)->toDateString()" :restore-old-input="false" min="{{ $today }}" required />
                                <button type="submit" class="m3-button m3-button--outlined">{{ __('Refer the concern') }}</button>
                            </form>
                            @endif
                        </details>
                    @endif
                    @if ($can_write && $item['mine'] && $item['concern'] === null)
                        <form method="POST" action="{{ route('early-years.observations.withdraw', ['observation' => $item['id']]) }}" aria-label="{{ __('Withdraw observation') }}">
                            @csrf
                            <input type="hidden" name="revision" value="{{ $item['revision'] }}">
                            <button type="submit" class="m3-button m3-button--text">{{ __('Withdraw observation') }}</button>
                        </form>
                    @endif
                </div></article>
            @endforeach
        </section>
        @endif
    @endforeach
    @if ($more)<p>{{ __('Only the latest 300 observations are shown.') }}</p>@endif
</div>
@endsection
