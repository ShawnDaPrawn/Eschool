@extends('layouts.master')
@section('title', __('Daily diary'))
@section('content')
@php($labels = ['meals' => __('Meals'), 'rest' => __('Rest'), 'mood' => __('Mood'), 'toileting' => __('Toileting')])
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Daily diary')" :subtitle="$section->full_name" />
    <p><a href="{{ route('early-years.class', ['section' => $section->id]) }}">{{ __('Back to the class') }}</a></p>
    @include('early-years.messages')

    <form method="GET" action="{{ route('early-years.diary', ['section' => $section->id]) }}" aria-label="{{ __('Choose a day') }}">
        <x-page.field id="diary-day" name="date" type="date" :label="__('Day')" :value="$date" :restore-old-input="false" required />
        <button type="submit" class="m3-button m3-button--outlined">{{ __('Open day') }}</button>
    </form>

    @if ($rows === [])
        <x-page.empty-state :title="__('No learners in this class today')" :message="__('Learners appear here while they are enrolled in the class.')" />
    @elseif ($canWrite)
        <form method="POST" action="{{ route('early-years.diary.save', ['section' => $section->id]) }}" aria-label="{{ __('Daily diary') }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <section class="page-section" aria-labelledby="diary-everyone">
                <h2 id="diary-everyone">{{ __('For everyone') }}</h2>
                <p>{{ __('Choices here fill every child\'s blank fields when you save, so the whole class is done at once. Change a child\'s own row where their day was different.') }}</p>
                @foreach ($choices as $field => $options)
                    <x-page.field :id="'diary-everyone-'.$field" :name="'everyone['.$field.']'" type="select" :label="$labels[$field]" :options="array_map(fn ($text) => __($text), $options)" :placeholder="__('Leave blank')" :restore-old-input="false" />
                @endforeach
            </section>
            <div class="page-table__scroll" role="region" aria-label="{{ __('Each child\'s day') }}" tabindex="0">
                <table class="page-table__table">
                    <caption>{{ __('Each child\'s day') }}</caption>
                    <thead><tr><th scope="col">{{ __('Child') }}</th>@foreach ($labels as $label)<th scope="col">{{ $label }}</th>@endforeach<th scope="col">{{ __('Note for the day') }}</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $i => $row)
                            <tr>
                                <th scope="row">{{ $row['name'] }}
                                    <input type="hidden" name="rows[{{ $i }}][learner_id]" value="{{ $row['learner_id'] }}">
                                    <input type="hidden" name="rows[{{ $i }}][revision]" value="{{ $row['revision'] }}">
                                </th>
                                @foreach ($choices as $field => $options)
                                    <td>
                                        <select name="rows[{{ $i }}][{{ $field }}]" aria-label="{{ $labels[$field] }}: {{ $row['name'] }}">
                                            <option value="">{{ __('Not recorded') }}</option>
                                            @foreach ($options as $value => $text)
                                                <option value="{{ $value }}" @selected($row[$field] === $value)>{{ __($text) }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                @endforeach
                                <td><input type="text" name="rows[{{ $i }}][note]" value="{{ $row['note'] }}" maxlength="500" aria-label="{{ __('Note for the day') }}: {{ $row['name'] }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="submit" class="m3-button m3-button--filled">{{ __('Save the diary') }}</button>
        </form>
    @else
        <p role="status">{{ __('Only teachers allocated to this class can fill in its diary.') }}</p>
        <div class="page-table__scroll" role="region" aria-label="{{ __('Each child\'s day') }}" tabindex="0">
            <table class="page-table__table">
                <caption>{{ __('Each child\'s day') }}</caption>
                <thead><tr><th scope="col">{{ __('Child') }}</th>@foreach ($labels as $label)<th scope="col">{{ $label }}</th>@endforeach<th scope="col">{{ __('Note for the day') }}</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr><th scope="row">{{ $row['name'] }}</th>
                            @foreach ($choices as $field => $options)<td>{{ $row[$field] === null ? '' : __($options[$row[$field]]) }}</td>@endforeach
                            <td>{{ $row['note'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
