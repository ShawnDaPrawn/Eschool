@extends('layouts.master')
@section('title', __('Learner progress and notes'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/progress-notes.css') }}">@endsection
@section('content')
<div class="content-wrapper progress-notes">
    <header class="note-heading"><div><h1>{{ __('Learner progress and notes') }}</h1><p>{{ __('Notes stay within their selected audience. Progress bands use assessed work, not risk labels.') }}</p></div></header>
    @if($messages)<div role="alert" class="alert alert-warning"><ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
    @if(session('success'))<p role="status" class="alert alert-success">{{ session('success') }}</p>@endif
    <nav class="note-tabs" aria-label="{{ __('Note workbench') }}">
        <a href="{{ route('learner-notes.index', ['mode' => 'academic']) }}" @if($mode==='academic') aria-current="page" @endif>{{ __('Teaching notes and progress') }}</a>
        @if($configuration['can_read_caseload'])<a href="{{ route('learner-notes.index', ['mode' => 'counselling']) }}" @if($mode==='counselling') aria-current="page" @endif>{{ __('Restricted counselling notes') }}</a>@endif
        @if($configuration['can_assign_counselling'])<a href="{{ route('learner-notes.index', ['mode' => 'assignments']) }}" @if($mode==='assignments') aria-current="page" @endif>{{ __('Counselling note assignments') }}</a>@endif
    </nav>
    <section class="note-card"><h2>{{ $mode==='assignments' ? __('Assign a note caseload') : ($mode==='counselling' ? __('Your assigned learners') : __('Choose a teaching group')) }}</h2>
        @if($mode==='counselling')<p>{{ __('Only an explicit note caseload grants access here. Support plans and other case assignments do not grant note access.') }}</p>@endif
        @if($mode==='assignments')<p>{{ __('Assignment administration shows metadata only and does not grant access to counselling text.') }}</p>@endif
        <form method="get" action="{{ route('learner-notes.index') }}" class="note-filters" aria-label="{{ __('Choose learner notes') }}">
            <input type="hidden" name="mode" value="{{ $mode }}">
            @if($mode==='academic')<label for="note-group">{{ __('Teaching group') }}<select id="note-group" name="group_id"><option value="">{{ __('Choose a group') }}</option>@foreach($configuration['groups'] as $g)<option value="{{ $g['id'] }}" @selected($groupId===$g['id'])>{{ $g['name'] }} · {{ $g['subject_name'] }}</option>@endforeach</select></label>@endif
            <label for="note-year">{{ __('School year') }}<select id="note-year" name="session_year_id">@foreach($configuration['years'] as $y)<option value="{{ $y['id'] }}" @selected($year===$y['id'])>{{ $y['name'] }}</option>@endforeach</select></label>
            @if($roster['learners'])<label for="note-learner">{{ __('Learner') }}<select id="note-learner" name="learner_id"><option value="">{{ __('Choose a learner') }}</option>@foreach($roster['learners'] as $l)<option value="{{ $l['learner_id'] }}" @selected($learnerId===$l['learner_id'])>{{ $l['display_name'] }}</option>@endforeach</select></label>@endif
            @if($mode==='academic')
                <label for="note-audience">{{ __('Note audience') }}<select id="note-audience" name="visibility"><option value="staff" @selected($visibility==='staff')>{{ __('Staff only') }}</option><option value="tutor" @selected($visibility==='tutor')>{{ __('Tutor-visible teaching notes') }}</option></select></label>
                @if($group)<label for="note-subject">{{ __('Note scope') }}<select id="note-subject" name="subject_id"><option value="">{{ __('General teaching note') }}</option><option value="{{ $group->subject_id }}" @selected($subject===(int)$group->subject_id)>{{ $group->subject->name }}</option></select></label>@endif
                <label for="note-term">{{ __('Progress term') }}<select id="note-term" name="term_id">@foreach($configuration['terms'] as $t)<option value="{{ $t['id'] }}" @selected($term===$t['id'])>{{ $t['name'] }}</option>@endforeach</select></label>
            @endif
            <button type="submit" class="btn btn-primary">{{ __('View notes') }}</button>
        </form>
        @if(($roster['last_page']??1)>1)<nav class="note-pages" aria-label="{{ __('Learner pages') }}">@if($roster['current_page']>1)<a href="{{ route('learner-notes.index', $selection+['page'=>$roster['current_page']-1]) }}">{{ __('Previous learners') }}</a>@endif @if($roster['current_page']<$roster['last_page'])<a href="{{ route('learner-notes.index', $selection+['page'=>$roster['current_page']+1]) }}">{{ __('More learners') }}</a>@endif</nav>@endif
    </section>
    @if($band)<section class="note-card" aria-label="{{ __('Current progress band') }}"><h2>{{ __('Current progress band') }}</h2><p><strong>{{ __('progress_band_'.$band['band']) }}</strong></p><p>{{ __('Assessed share') }}: {{ number_format($band['coverage_bp']/100, 2) }}% · {{ __('Running mark') }}: {{ $band['percentage_bp']===null ? __('Not assessed') : number_format($band['percentage_bp']/100, 2).'%' }}</p><p>{{ __('This staff view may include unreleased work. Family views use released evidence only.') }}</p></section>@endif
    @if($learnerId>0 && $mode!=='assignments')
        <div class="note-columns"><section class="note-card"><h2>{{ __('Saved notes') }}</h2>
            @forelse($listing['notes'] as $item)<article class="note-list-item"><h3><a href="{{ route('learner-notes.index', array_replace($selection, ['note'=>$item['id']])) }}">{{ $item['author_name'] }} · {{ \Carbon\Carbon::parse($item['updated_at'])->format('j M Y H:i') }}</a></h3><p>{{ __('Revision') }} {{ $item['revision'] }}</p></article>@empty<p>{{ __('No notes are saved for this selection.') }}</p>@endforelse
            @if($listing['next_after'])<a href="{{ route('learner-notes.index', $selection+['after'=>$listing['next_after']]) }}">{{ __('More saved notes') }}</a>@endif
            @if($record)<a class="btn btn-outline-primary" href="{{ route('learner-notes.index', $selection) }}">{{ __('Write a new note') }}</a>@endif
        </section><section class="note-card"><h2>{{ $record ? __('Note detail') : __('Write a new note') }}</h2>
            @if($visibility==='tutor')<p>{{ __('Only include teaching information appropriate for the learner tutor. Counselling belongs in the restricted workbench.') }}</p>@endif
            @if($visibility==='counselling')<p>{{ __('Restricted counselling text is never used by the tutor or family progress summaries.') }}</p>@endif
            @if($record)<p>{{ $record['author_name'] }} · {{ __('Revision') }} {{ $record['revision'] }}</p><div class="note-body" aria-label="{{ __('Current saved note') }}">{{ $record['body'] }}</div>@endif
            @if($canWrite && (!$record || ($record['author_id']===$actorId && !$record['archived'])))
                @php($operation=$record ? 'update' : 'create')
                <form method="post" action="{{ $record ? route('learner-notes.update', $record['id']) : route('learner-notes.create', $learnerId) }}" aria-label="{{ $record ? __('Edit own note') : __('Create learner note') }}">
                    @include('growth.note-form-identity',['operation'=>$operation,'target'=>$record['id']??$learnerId])
                    @foreach(($record ? array_intersect_key($selection,array_flip(['mode','group_id','term_id'])) : $selection) as $key=>$value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                    @if($record)<input type="hidden" name="expected_revision" value="{{ $record['revision'] }}">@endif
                    <label for="private-note-body">{{ $conflict ? __('Your unsaved note draft') : __('Private note text') }}</label><textarea id="private-note-body" name="body" rows="9" autocomplete="off" spellcheck="false" maxlength="20000" required>{{ $draft ?? $record['body'] ?? '' }}</textarea>
                    @if($record)<label for="archive-note"><input id="archive-note" type="checkbox" name="archive" value="1"> {{ __('Archive this note after saving') }}</label>@endif
                    <button type="submit" class="btn btn-primary">{{ $record ? __('Save note revision') : __('Save new note') }}</button>
                </form>
            @else<p>{{ __('This note is read-only. Only its current authorized author can revise it in the current school year.') }}</p>@endif
            @if($history)<details><summary>{{ __('Retained note history') }}</summary>@foreach($history as $version)<article class="note-history"><h3>{{ __('Revision') }} {{ $version['revision'] }} · {{ $version['author_name'] }}</h3><div class="note-body">{{ $version['body'] }}</div></article>@endforeach</details>@endif
        </section></div>
    @endif
    @if($mode==='assignments' && $learnerId>0)<section class="note-card"><h2>{{ __('Counselling note assignments') }}</h2>
        @foreach($assignments as $assignment)
            <article class="note-history"><h3>{{ $assignment['staff_name'] }}</h3><p>{{ $assignment['starts_on'] }} — {{ $assignment['ends_on'] }} · {{ __('Revision') }} {{ $assignment['revision'] }} · {{ $assignment['revoked'] ? __('Access ended') : __('Dated access') }}</p>
            @if($canWrite && !$assignment['revoked'])<form method="post" action="{{ route('learner-notes.revoke-assignment',$learnerId) }}" aria-label="{{ __('End note access for') }} {{ $assignment['staff_name'] }}">
                @include('growth.note-form-identity',['operation'=>'revoke-assignment','target'=>$assignment['id']])
                <input type="hidden" name="mode" value="assignments"><input type="hidden" name="staff_user_id" value="{{ $assignment['staff_user_id'] }}"><input type="hidden" name="expected_revision" value="{{ $assignment['revision'] }}">
                <button type="submit" class="btn btn-outline-danger">{{ __('End note access now') }}</button>
            </form>@endif</article>
        @endforeach
        @if($canWrite)<form method="post" action="{{ route('learner-notes.assign',$learnerId) }}" class="note-filters" aria-label="{{ __('Save note caseload assignment') }}">
            @include('growth.note-form-identity',['operation'=>'assign','target'=>$learnerId])
            @php($assignmentRevisions=collect($assignments)->pluck('revision','staff_user_id'))
            <input type="hidden" name="mode" value="assignments"><input id="assignment-revision" type="hidden" name="expected_revision" value="{{ $assignmentRevisions[$configuration['restricted_staff'][0]['id']??0]??0 }}">
            <label for="note-staff">{{ __('Restricted staff member') }}<select id="note-staff" name="staff_user_id" required>@foreach($configuration['restricted_staff'] as $person)<option value="{{ $person['id'] }}" data-revision="{{ $assignmentRevisions[$person['id']]??0 }}">{{ $person['name'] }}</option>@endforeach</select></label>
            <label for="note-start">{{ __('First access date') }}<input id="note-start" type="date" name="starts_on" required></label><label for="note-end">{{ __('Last access date') }}<input id="note-end" type="date" name="ends_on" required></label><button type="submit" class="btn btn-primary">{{ __('Save assignment') }}</button>
        </form>@endif
    </section>@endif
    @if($configuration['can_manage_bands'])<details class="note-card"><summary>{{ __('School progress-band settings') }}</summary><p>{{ __('Choose percentage thresholds and the minimum assessed share before progress alerts are offered.') }}</p>
        <form method="post" action="{{ route('learner-notes.configure') }}" class="note-filters" aria-label="{{ __('Update school progress bands') }}">
            @include('growth.note-form-identity',['operation'=>'configure','target'=>'school'])
            <input type="hidden" name="expected_revision" value="{{ $configuration['settings']['revision'] }}">
            @foreach(['high_pct'=>__('High band threshold'),'secure_pct'=>__('Secure band threshold'),'minimum_coverage_pct'=>__('Minimum assessed share')] as $key=>$label)<label for="band-{{ $key }}">{{ $label }}<input id="band-{{ $key }}" type="number" name="{{ $key }}" min="{{ $key==='secure_pct'?'0':'0.01' }}" max="100" step="0.01" value="{{ number_format($configuration['settings'][str_replace('_pct','_bp',$key)]/100,2,'.','') }}" required></label>@endforeach
            <button type="submit" class="btn btn-primary" @disabled(!$canWrite)>{{ __('Save band settings') }}</button>
        </form>
    </details>@endif
</div>
@endsection

@section('script')<script src="{{ asset('assets/js/progress-notes.js') }}" defer></script>@endsection
