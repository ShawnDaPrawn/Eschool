@extends('layouts.master')
@section('title', __('Early years'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Early years')" :subtitle="__('The daily diary, observations and each child\'s portfolio for your class.')" />
    @include('early-years.messages')

    <section class="page-section" aria-labelledby="early-years-classes">
        <h2 id="early-years-classes">{{ __('Classes') }}</h2>
        @if ($classes->isEmpty())
            <x-page.empty-state :title="__('No classes yet')" :message="__('Teachers see the classes they are allocated to as homeroom teacher this year.')" />
        @else
            <ul>
                @foreach ($classes as $class)
                    <li>
                        <a href="{{ route('early-years.class', ['section' => $class->id]) }}">{{ $class->full_name }}</a>
                        · <a href="{{ route('early-years.diary', ['section' => $class->id]) }}">{{ __('Today\'s diary') }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="page-section" aria-labelledby="early-years-areas">
        <h2 id="early-years-areas">{{ __('Development areas') }}</h2>
        <p>{{ __('Each observation is tagged to one of these areas. Switched-off areas keep their observations but cannot be chosen for new ones.') }}</p>
        @if (! $manage)
            <ul>
                @foreach ($areas as $area)
                    <li>{{ $area->name }}@if (! $area->active) ({{ __('switched off') }})@endif</li>
                @endforeach
            </ul>
        @else
            @foreach ($areas as $area)
                <form method="POST" action="{{ route('early-years.areas.update', ['area' => $area->id]) }}" class="m3-card m3-card--outlined" aria-label="{{ __('Development area') }} {{ $area->name }}">
                    <div class="m3-card__content">
                        @csrf
                        <input type="hidden" name="revision" value="{{ $area->revision }}">
                        <input type="hidden" name="active" value="0">
                        <x-page.field :id="'area-name-'.$area->id" name="name" :label="__('Name')" :value="$area->name" :restore-old-input="false" maxlength="80" required />
                        <x-page.field :id="'area-description-'.$area->id" name="description" :label="__('Description')" :value="$area->description" :restore-old-input="false" maxlength="255" />
                        <x-page.field :id="'area-position-'.$area->id" name="position" type="number" :label="__('Order')" :value="$area->position" :restore-old-input="false" min="0" max="999" />
                        <label for="area-active-{{ $area->id }}"><input id="area-active-{{ $area->id }}" type="checkbox" name="active" value="1" @checked($area->active)> {{ __('Switched on') }}</label>
                        <button type="submit" class="m3-button m3-button--outlined">{{ __('Save area') }}</button>
                    </div>
                </form>
            @endforeach
            <form method="POST" action="{{ route('early-years.areas.store') }}" class="m3-card m3-card--outlined" aria-label="{{ __('Add a development area') }}">
                <div class="m3-card__content">
                    @csrf
                    <h3>{{ __('Add a development area') }}</h3>
                    <input type="hidden" name="active" value="1">
                    <x-page.field id="area-new-name" name="name" :label="__('Name')" :restore-old-input="false" maxlength="80" required />
                    <x-page.field id="area-new-description" name="description" :label="__('Description')" :restore-old-input="false" maxlength="255" />
                    <button type="submit" class="m3-button m3-button--filled">{{ __('Add area') }}</button>
                </div>
            </form>
        @endif
    </section>
</div>
@endsection
