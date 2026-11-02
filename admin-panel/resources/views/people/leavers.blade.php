@extends('layouts.master')
@section('title', __('Leavers'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Leavers')" :subtitle="__('Learners who left at the start of the school year. Until the date shown, they and their guardians can read their reports, marks and documents in the app, and change nothing. They are not in any class list, register or group.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <section class="page-section" aria-labelledby="leavers-list">
        <h2 id="leavers-list">{{ __('Leavers') }}</h2>
        @if ($leavers->isEmpty())
            <x-page.empty-state :title="__('No leavers')" :message="__('Learners marked as leaving appear here once their new school year becomes current.')" />
        @else
            <div class="page-table__scroll" role="region" aria-label="{{ __('Leavers') }}" tabindex="0"><table class="page-table__table">
                <caption class="m3-visually-hidden">{{ __('Leavers') }}</caption>
                <thead><tr>
                    <th scope="col">{{ __('Learner') }}</th>
                    <th scope="col">{{ __('Admission number') }}</th>
                    <th scope="col">{{ __('Last homeroom') }}</th>
                    <th scope="col">{{ __('Access') }}</th>
                    @if ($canEdit)<th scope="col">{{ __('Actions') }}</th>@endif
                </tr></thead>
                <tbody>
                @foreach ($leavers as $leaver)
                    @php
                        $name = trim(($leaver->user?->first_name ?? '').' '.($leaver->user?->last_name ?? ''));
                        $reading = $leaver->leaver_status === \App\Models\Platform\SchoolMembership::LEAVER && \Illuminate\Support\Carbon::parse($leaver->leaver_until)->toDateString() >= $today;
                        $endedOn = $reading ? null : \Illuminate\Support\Carbon::parse($leaver->leaver_status === \App\Models\Platform\SchoolMembership::LEAVER ? $leaver->leaver_until : $leaver->leaver_ends_on)->format('j M Y');
                    @endphp
                    <tr>
                        <th scope="row">{{ $name }}</th>
                        <td>{{ $leaver->admission_no }}</td>
                        <td>{{ trim(($leaver->class_section?->class?->name ?? '').' '.($leaver->class_section?->section?->name ?? '')) }}</td>
                        <td>
                            @if ($reading)
                                <span class="m3-chip">{{ __('Leaver: read-only until :date', ['date' => \Illuminate\Support\Carbon::parse($leaver->leaver_until)->format('j M Y')]) }}</span>
                            @else
                                {{ __('Access ended :date', ['date' => $endedOn]) }}
                            @endif
                        </td>
                        @if ($canEdit)
                            <td>
                                <form method="POST" action="{{ route('leavers.restore', $leaver->id) }}" aria-label="{{ __('Bring :name back', ['name' => $name]) }}">
                                    @csrf
                                    <label for="leaver-homeroom-{{ $leaver->id }}">{{ __('Homeroom') }}</label>
                                    <select name="homeroom_id" id="leaver-homeroom-{{ $leaver->id }}" required>
                                        <option value="">{{ __('Choose a homeroom') }}</option>
                                        @foreach ($homerooms as $id => $room)
                                            <option value="{{ $id }}" @selected((int) $leaver->class_section_id === (int) $id)>{{ $room }}</option>
                                        @endforeach
                                    </select>
                                    <button class="m3-button m3-button--outlined" type="submit">{{ __('Bring back as a returning learner') }}</button>
                                </form>
                                @if ($reading)
                                    <form method="POST" action="{{ route('leavers.end', $leaver->id) }}" aria-label="{{ __('End access for :name', ['name' => $name]) }}">
                                        @csrf
                                        <input type="hidden" name="confirm" value="1">
                                        <button class="m3-button m3-button--text" type="submit" data-confirm="{{ __('End :name\'s read-only access today? Their guardians lose it too unless another of their children is at the school.', ['name' => $name]) }}">{{ __('End access now') }}</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table></div>
            {{ $leavers->links() }}
        @endif
    </section>
</div>
@endsection
