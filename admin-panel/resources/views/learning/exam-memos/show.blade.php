@extends('layouts.master')
@section('title', __('Exam coach memo'))
@section('content')
@php
    $decimal = fn (int $units) => \App\Services\Academics\AcademicNumbers::decimal($units);
    $memoRows = [];
    if ($can_edit) {
        foreach ($questions as $i => $question) {
            $memoRows[$i] = min(\App\Services\Learning\PastPaperMemos::MAX_POINTS, max(count($question['points']), count((array) old('questions.'.$i.'.points', []))) + 2);
        }
    }
    $nativeInputCount = 2 + count($questions) + 7 * array_sum($memoRows);
    $nativeInputLimit = (int) ini_get('max_input_vars');
@endphp
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Exam coach memo')" :subtitle="$paper['title'].' · '.$paper['paper_year'].' '.$paper['paper_code']" />
    <a href="{{ route('exam-memos.index', ['group' => $group_id]) }}">{{ __('All past papers') }}</a>
    @if (session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if ($errors->any())<div role="alert" class="m3-alert">@foreach ($errors->all() as $problem)<p>{{ $problem }}</p>@endforeach</div>@endif

    <section class="page-section" aria-labelledby="memo-state">
        <h2 id="memo-state">{{ __('Memo status') }}</h2>
        <p role="status">
            @if ($memo['state'] === 'reviewed')
                {{ __('Reviewed by :name on :date. Its mark points can be used for marking.', ['name' => $memo['reviewed_by'] ?? __('a teacher'), 'date' => $memo['reviewed_at']]) }}
            @elseif ($memo['state'] === 'draft')
                {{ __('Mark points not reviewed. They are not used for marking until a teacher reviews the memo.') }}
            @else
                {{ __('No mark points yet.') }}
            @endif
        </p>
        <p>{{ __('Rights: :kind · :reference', ['kind' => $paper['licence_kind'], 'reference' => $paper['licence_reference']]) }}</p>
        @if ($memo['problems'] !== [])
            <ul>@foreach ($memo['problems'] as $problem)<li>{{ $problem }}</li>@endforeach</ul>
        @endif
        @if ($can_edit && $memo['state'] === 'draft' && $memo['problems'] === [])
            <form method="POST" action="{{ route('exam-memos.review', ['group' => $group_id, 'version' => $paper['version_id']]) }}" aria-label="{{ __('Review the memo') }}">
                @csrf
                <input type="hidden" name="expected_revision" value="{{ $memo['revision'] }}">
                <label for="memo-confirmed"><input id="memo-confirmed" type="checkbox" name="confirmed" value="1" required> {{ __('I have checked every mark point against the memo.') }}</label>
                <button type="submit" class="m3-button m3-button--filled">{{ __('Mark the memo as reviewed') }}</button>
            </form>
        @endif
    </section>

    @if ($can_edit)
    <form method="POST" action="{{ route('exam-memos.save', ['group' => $group_id, 'version' => $paper['version_id']]) }}" aria-label="{{ __('Mark points') }}" data-exam-memo-form>
        @csrf
        <input type="hidden" name="expected_revision" value="{{ $memo['revision'] }}">
        @if ($nativeInputLimit > 0 && $nativeInputCount > $nativeInputLimit)
            {{-- The listener replaces this; missing JavaScript must fail validation before truncated rows can be saved. --}}
            <input type="hidden" name="memo_payload" value="">
        @endif
        <p>{{ __('The mark points of each question must add up to its total. Leave a row empty to skip it. Saving changes a reviewed memo back to not reviewed.') }}</p>
    @endif
    @foreach ($questions as $i => $question)
        <section class="m3-card m3-card--outlined page-section" aria-labelledby="memo-question-{{ $i }}"><div class="m3-card__content">
            <h2 id="memo-question-{{ $i }}">{{ __('Question :number (:total marks)', ['number' => $question['number'], 'total' => $decimal($question['max_units'])]) }}</h2>
            <p dir="auto" style="white-space:pre-wrap">{{ $question['prompt'] }}</p>
            <h3>{{ __('Memo') }}</h3>
            <p dir="auto" style="white-space:pre-wrap">{{ $question['memo'] }}</p>
            <p role="status">{{ __('Mark points add up to :sum of :total.', ['sum' => $decimal($question['sum_units']), 'total' => $decimal($question['max_units'])]) }}</p>
            @if ($can_edit)
                <input type="hidden" name="questions[{{ $i }}][number]" value="{{ $question['number'] }}">
                @php($rows = $memoRows[$i])
                @for ($j = 0; $j < $rows; $j++)
                    @php($point = $question['points'][$j] ?? null)
                    @php($rule = $point['rule'] ?? null)
                    <fieldset style="min-width:0;border:0;padding:0;margin-block:12px"><legend>{{ __('Mark point :n', ['n' => $j + 1]) }}</legend>
                        @php($base = 'questions['.$i.'][points]['.$j.']')
                        <x-page.field :id="'point-'.$i.'-'.$j.'-earns'" :name="$base.'[earns]'" :label="__('What earns the mark')" :value="$point['earns'] ?? ''" maxlength="500" dir="auto" />
                        <x-page.field :id="'point-'.$i.'-'.$j.'-type'" :name="$base.'[type]'" type="select" :label="__('Type of mark')" :options="$types" :value="$point['type'] ?? 'method'" />
                        <x-page.field :id="'point-'.$i.'-'.$j.'-marks'" :name="$base.'[marks]'" :label="__('Marks')" :value="$point === null ? '' : $decimal($point['mark_units'])" inputmode="decimal" maxlength="8" />
                        <x-page.field :id="'point-'.$i.'-'.$j.'-rule'" :name="$base.'[rule_kind]'" type="select" :label="__('Rule')" :options="$ruleKinds" :value="$rule['kind'] ?? 'none'" />
                        <x-page.field :id="'point-'.$i.'-'.$j.'-value'" :name="$base.'[rule_value]'" :label="__('Exact value')" :value="$rule['value'] ?? ''" inputmode="decimal" maxlength="22" />
                        <x-page.field :id="'point-'.$i.'-'.$j.'-tolerance'" :name="$base.'[rule_tolerance]'" :label="__('Tolerance (plus or minus)')" :value="$rule['tolerance'] ?? ''" inputmode="decimal" maxlength="22" />
                        <x-page.field :id="'point-'.$i.'-'.$j.'-unit'" :name="$base.'[rule_unit]'" :label="__('Unit')" :value="$rule['unit'] ?? ''" maxlength="32" />
                    </fieldset>
                @endfor
            @elseif ($question['points'] === [])
                <p>{{ __('No mark points yet.') }}</p>
            @else
                <ol>
                    @foreach ($question['points'] as $point)
                        <li dir="auto">{{ $point['earns'] }} · {{ $types[$point['type']] ?? $point['type'] }} · {{ $decimal($point['mark_units']) }}
                            @if (($point['rule']['kind'] ?? null) === 'exact_value') · {{ __('Exact value :value (± :tolerance) :unit', ['value' => $point['rule']['value'], 'tolerance' => $point['rule']['tolerance'], 'unit' => $point['rule']['unit'] ?? '']) }}
                            @elseif (($point['rule']['kind'] ?? null) === 'units_required') · {{ __('Units required: :unit', ['unit' => $point['rule']['unit']]) }}@endif</li>
                    @endforeach
                </ol>
            @endif
        </div></section>
    @endforeach
    @if ($can_edit)
        <button type="submit" class="m3-button m3-button--filled">{{ __('Save the mark points') }}</button>
    </form>
    @endif
</div>
@endsection

@section('script')<script src="{{ asset('assets/js/custom/exam-memo-form.js') }}" defer></script>@endsection
