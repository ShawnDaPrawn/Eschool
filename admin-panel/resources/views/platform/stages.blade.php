@extends('layouts.master')
@section('title', __('School stages'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('School stages')" :subtitle="__('Each grade belongs to an age stage. Early years learners do not sign in themselves: their parents and teachers use NavigateEd for them. Some learner features are offered only in the junior and high school stages.')" />
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @foreach ($errors->all() as $problem)<p role="alert">{{ $problem }}</p>@endforeach

    <form method="POST" action="{{ route('school-stages.update') }}" aria-label="{{ __('School stages') }}">
        @csrf
        @method('PUT')
        <div class="m3-card m3-card--outlined page-table">
            <div class="page-table__scroll" role="region" aria-label="{{ __('Stage of each grade') }}" tabindex="0">
            <table class="page-table__table">
                <caption>{{ __('Stage of each grade') }}</caption>
                <thead><tr><th scope="col">{{ __('Grade') }}</th><th scope="col">{{ __('School phase') }}</th><th scope="col">{{ __('Stage') }}</th><th scope="col">{{ __('NavigateEd default') }}</th></tr></thead>
                <tbody>
                @foreach ($grades as $grade)
                    <tr>
                        <th scope="row">{{ $grade['grade'] }}</th>
                        <td>{{ __($grade['phase']) }}</td>
                        <td>
                            <select name="stages[{{ $grade['grade_level_id'] }}]" id="stage-{{ $grade['grade_level_id'] }}" aria-label="{{ __('Stage for :grade', ['grade' => $grade['grade']]) }}">
                                @foreach ($stages as $key => $label)
                                    <option value="{{ $key }}" @selected($grade['stage'] === $key)>{{ __($label) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>{{ __($stages[$grade['default']]) }}@if ($grade['stage'] !== $grade['default']) ({{ __('changed by the school') }})@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        <button class="m3-button m3-button--filled" type="submit">{{ __('Save stages') }}</button>
    </form>
</div>
@endsection
