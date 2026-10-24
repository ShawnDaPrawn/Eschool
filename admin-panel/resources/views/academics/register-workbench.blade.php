@extends('layouts.master')
@section('title', __('Academic registers'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/register-workbench.css') }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content register-workbench" data-register-workbench>
    <x-page.header :title="__('Academic registers')" :subtitle="__('Capture attendance, review missing registers and follow up recorded absences.')" />
    @include('academics.groups-status')
    <form method="GET" action="{{ route('academic-registers.index') }}" class="register-fields" aria-label="{{ __('Choose register date') }}">
        <x-page.field name="on_date" type="date" :label="__('Register date')" :value="$choices['on_date']" required />
        <button type="submit" class="m3-button m3-button--outlined">{{ __('View registers') }}</button>
    </form>
    <section aria-labelledby="register-overview-title" class="page-section">
        <h2 id="register-overview-title">{{ __('Homeroom follow-up') }} · {{ $choices['on_date'] }}</h2>
        <p>{{ __('Missing entries stay unmarked. Only captured absences trigger parent alerts.') }}</p>
        <div class="register-overview">
            @forelse($overview as $room)
                <article class="m3-card m3-card--outlined"><div class="m3-card__content"><h3>{{ $room['name'] }}</h3>
                    <p>{{ __('Recorded') }}: {{ $room['recorded'] }}/{{ $room['expected'] }} · {{ __('Unmarked') }}: {{ $room['missing'] }} · {{ __('Absent') }}: {{ $room['absent'] }}</p>
                    <form method="POST" action="{{ route('academic-registers.open') }}">@csrf
                        <input type="hidden" name="scope" value="homeroom:{{ $room['id'] }}"><input type="hidden" name="session_year_id" value="{{ $choices['session_year_id'] }}"><input type="hidden" name="on_date" value="{{ $choices['on_date'] }}">
                        <button type="submit" class="m3-button m3-button--outlined">{{ __('Open register') }}: {{ $room['name'] }}</button>
                    </form>
                </div></article>
            @empty<p>{{ __('No allocated homerooms on this date.') }}</p>@endforelse
        </div>
    </section>
    @if(count($choices['periods']))
        <form method="POST" action="{{ route('academic-registers.open') }}" aria-label="{{ __('Open period register') }}" class="register-fields">@csrf
            <input type="hidden" name="session_year_id" value="{{ $choices['session_year_id'] }}"><input type="hidden" name="on_date" value="{{ $choices['on_date'] }}">
            <x-page.field name="scope" type="select" :label="__('Lesson')" :options="collect($choices['periods'])->mapWithKeys(fn ($p) => ['lesson:'.$p['id'] => $p['name']])->all()" required />
            <button type="submit" class="m3-button m3-button--outlined">{{ __('Open period register') }}</button>
        </form>
    @endif
    @if($matrix)
        @php $states = ['unmarked' => __('Unmarked'), 'present' => __('Present'), 'absent' => __('Absent'), 'late' => __('Late'), 'excused' => __('Excused')]; if ($matrix['timetable_lesson_id'] === null) $states += ['first_half' => __('First half'), 'second_half' => __('Second half'), 'holiday' => __('Holiday')]; @endphp
        <section aria-labelledby="capture-title" class="m3-card m3-card--outlined page-section"><div class="m3-card__content">
            <h2 id="capture-title">{{ $matrix['label'] }} · {{ $matrix['on_date'] }}</h2>
            @if(!$matrix['can_write'])<p role="status">{{ __('Read only. Ask the grade head to correct an earlier register.') }}</p>@endif
            @if($conflict)
                <div role="alert"><h3>{{ __('Review changes before saving') }}</h3><p>{{ $conflict }}</p><p>{{ __('The saved register is shown below. Compare your entries, then explicitly choose to prepare a new draft.') }}</p></div>
                <ul>@foreach($submitted as $row) @php $learner = collect($matrix['learners'])->firstWhere('learner_id', (int) $row['learner_id']); @endphp
                    @if($learner)<li>{{ $learner['name'] }}: {{ __('Saved') }} {{ $states[$learner['state']] ?? $learner['state'] }} · {{ __('Your entry') }} {{ $states[$row['state']] ?? $row['state'] }}</li>@endif
                @endforeach</ul>
                <form method="POST" action="{{ route('academic-registers.review') }}" aria-label="{{ __('Prepare reviewed register') }}">@csrf
                    <input type="hidden" name="register_id" value="{{ $matrix['register_id'] }}"><input type="hidden" name="payload" value="{{ json_encode($submitted, JSON_THROW_ON_ERROR) }}"><input type="hidden" name="expected_row_count" value="{{ count($submitted) }}"><input type="hidden" name="reason" value="{{ $reason }}">
                    <button type="submit" class="m3-button m3-button--outlined" @disabled(!$matrix['can_write'])>{{ __('Prepare reviewed draft') }}</button>
                </form>
            @endif
            @if(count($draft))<p role="status">{{ __('Reviewed entries are a draft. Check them and save to apply the correction.') }}</p>@endif
            <form method="POST" action="{{ route('academic-registers.sync') }}" aria-label="{{ __('Capture register') }}" data-register-form>@csrf
                <input type="hidden" name="register_id" value="{{ $matrix['register_id'] }}"><input type="hidden" name="operation_id" value="{{ $operationId }}"><input type="hidden" name="base_revision" value="{{ $matrix['revision'] }}"><input type="hidden" name="roster_hash" value="{{ $matrix['roster_hash'] }}"><input type="hidden" name="expected_row_count" value="{{ count($matrix['learners']) }}"><input type="hidden" name="payload" value="" data-register-payload>
                <fieldset @disabled(!$matrix['can_write'] || $conflict)>
                    <legend>{{ __('Attendance entries') }}</legend>
                    <button type="button" class="m3-button m3-button--outlined" data-mark-present disabled>{{ __('Mark remaining present') }}</button>
                    <label for="register-qr">{{ __('Scan or paste learner QR') }}</label><input id="register-qr" type="text" autocomplete="off" maxlength="4096" data-register-qr disabled>
                    <p role="status" data-qr-status data-found="{{ __('Learner marked present. Save the register to apply.') }}" data-unknown="{{ __('QR does not match this roster. Use the tap list or reload the register.') }}"></p>
                    <div class="register-table" tabindex="0" role="region" aria-label="{{ __('Attendance list') }}"><table class="register-data-table"><thead><tr><th scope="col">{{ __('Learner') }}</th><th scope="col">{{ __('Attendance') }}</th><th scope="col">{{ __('Last amended') }}</th></tr></thead><tbody>
                    @foreach($matrix['learners'] as $learner)
                        <tr data-register-row data-learner-id="{{ $learner['learner_id'] }}" data-revision="{{ $learner['revision'] }}" data-qr-code="{{ $learner['qr_code'] }}">
                            <th scope="row">{{ $learner['name'] }}</th><td>
                                <label class="register-visually-hidden" for="attendance-{{ $learner['learner_id'] }}">{{ __('Attendance for') }} {{ $learner['name'] }}</label>
                                <select id="attendance-{{ $learner['learner_id'] }}" name="records[{{ $learner['learner_id'] }}][state]" class="register-state-input" data-register-state>@foreach($states as $value => $label)<option value="{{ $value }}" @selected($value === ($draft[$learner['learner_id']] ?? old('records.'.$learner['learner_id'].'.state', $learner['state'])))>{{ $label }}</option>@endforeach</select>
                                <input type="hidden" name="records[{{ $learner['learner_id'] }}][expected_revision]" value="{{ $learner['revision'] }}">
                            </td><td>{{ $learner['amended_at'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody></table></div>
                    <x-page.field name="reason" type="textarea" :label="__('Reason for correction')" :value="$reason" :help="__('Required for changes to captured attendance or an earlier day.')" maxlength="500" />
                    <button type="submit" class="m3-button m3-button--filled" @disabled(!count($matrix['learners']))>{{ __('Save register') }}</button>
                </fieldset>
            </form>
        </div></section>
    @endif
    @if($choices['can_manage'])
        <details class="page-section"><summary>{{ __('Absence alert settings') }}</summary>
            <p>{{ __('Cut-off uses the school timezone. Approved leave suppresses absence alerts.') }}</p>
            <form method="POST" action="{{ route('academic-registers.settings') }}" aria-label="{{ __('Save absence settings') }}">@csrf
                <x-page.field name="absence_cutoff" type="time" :label="__('Absence alert cut-off')" :value="$choices['settings']['absence_cutoff']" required />
                <fieldset><legend>{{ __('Teaching days') }}</legend>@foreach([1 => __('Monday'), 2 => __('Tuesday'), 3 => __('Wednesday'), 4 => __('Thursday'), 5 => __('Friday'), 6 => __('Saturday'), 7 => __('Sunday')] as $number => $day)<label><input type="checkbox" name="teaching_days[]" value="{{ $number }}" @checked(in_array($number, $choices['settings']['teaching_days'], true))> {{ $day }}</label> @endforeach</fieldset>
                <button type="submit" class="m3-button m3-button--filled">{{ __('Save absence settings') }}</button>
            </form>
        </details>
    @endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/custom/register-workbench.js') }}" defer></script>@endsection
