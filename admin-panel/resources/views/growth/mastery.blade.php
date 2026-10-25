@extends('layouts.master')
@section('title', __('Mastery map'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/mastery-workbench.css') }}?v={{ filemtime(public_path('assets/css/mastery-workbench.css')) }}">@endsection
@section('content')
<div class="content-wrapper m3 page-content mastery-workbench">
    <x-page.header :title="__('Mastery map')" :subtitle="__('Levels use recent evidence. They are learning guidance, not a promotion decision.')" />
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert"><h2>{{ __('Review these changes') }}</h2><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="mastery-columns">
        <aside class="mastery-panel">
            <h2>{{ __('Choose learner evidence') }}</h2>
            <form method="GET" action="{{ route('mastery.index') }}" class="mastery-form" aria-label="{{ __('Choose teaching group') }}">
                <input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}">
                <x-page.field type="select" :restore-old-input="false" name="group_id" :label="__('Teaching group')" :options="collect($configuration['groups'])->mapWithKeys(fn($g)=>[$g['id']=>$g['name'].' · '.$g['subject_name'].' · '.$g['grade_name']])->all()" :value="$group?->id" />
                <button class="m3-button m3-button--tonal" type="submit">{{ __('Open group') }}</button>
            </form>
            @if($group && $roster)
            <form method="GET" action="{{ route('mastery.index') }}" class="mastery-form" aria-label="{{ __('Choose learner') }}">
                <input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}"><input type="hidden" name="group_id" value="{{ $group->id }}"><input type="hidden" name="page" value="{{ $roster['current_page'] }}">
                <x-page.field type="select" :restore-old-input="false" name="learner_id" :label="__('Learner')" :options="array_column($roster['learners'],'display_name','learner_id')" :value="$learnerId" />
                <button class="m3-button m3-button--tonal" type="submit">{{ __('Open learner') }}</button>
            </form>
            <nav class="mastery-pagination" aria-label="{{ __('Learner pages') }}">
                @if($roster['current_page']>1)<a href="{{ route('mastery.index',['group_id'=>$group->id,'page'=>$roster['current_page']-1,'session_year_id'=>$configuration['session_year_id']]) }}">{{ __('Previous page') }}</a>@endif
                <span>{{ $roster['current_page'] }} / {{ $roster['last_page'] }}</span>
                @if($roster['current_page']<$roster['last_page'])<a href="{{ route('mastery.index',['group_id'=>$group->id,'page'=>$roster['current_page']+1,'session_year_id'=>$configuration['session_year_id']]) }}">{{ __('Next page') }}</a>@endif
            </nav>
            @if($writable)<form method="POST" action="{{ route('mastery.rebuild',['group'=>$group->id]) }}">@csrf<button class="m3-button m3-button--outlined" type="submit">{{ __('Refresh learner evidence') }}</button></form>@endif
            @endif
            <p>{{ __('Recent question evidence is bounded. Missing evidence remains unknown.') }}</p>
        </aside>
        <section class="mastery-panel" aria-labelledby="mastery-guidance-heading">
            <h2 id="mastery-guidance-heading">{{ __('Start with these topics') }}</h2>
            @if($view)
                @php $topicNames=array_column($view['topics'],'name','id'); @endphp
                @forelse($view['priorities'] as $priority)
                    <article class="mastery-topic"><h3>{{ $priority['name'] }}</h3><p>{{ $priority['level'] === null ? __('Not yet assessed') : __('Level').' '.$priority['level'].' / 4' }}</p>
                    <p>{{ match($priority['reason']) {'not_yet_assessed'=>__('Try this topic to establish a starting point.'),'prerequisite_gap'=>__('This earlier topic supports your current work.'),default=>__('More practice on this topic may help.')} }}</p>
                    @if(count($priority['path'])>1)<p>{{ __('Learning path') }}: {{ implode(' → ',array_map(fn($id)=>$topicNames[$id] ?? '',$priority['path'])) }}</p>@endif</article>
                @empty<p>{{ count($view['topics']) ? __('No current gaps are identified in the available evidence.') : __('No published topics are available for this subject yet.') }}</p>@endforelse
                <h2>{{ __('Topic evidence') }}</h2><div class="mastery-evidence">
                @foreach($view['topics'] as $topic)<article class="mastery-topic"><h3>{{ $topic['name'] }}</h3><p>{{ $topic['grade_name'] }} · {{ $topic['level'] === null ? __('Not yet assessed') : __('Level').' '.$topic['level'].' / 4' }}</p>
                    @if(!in_array($topic['id'],$view['target_topic_ids'],true))<p>{{ __('Prerequisite topic') }}</p>@endif
                    <p>{{ __('Recent questions') }}: {{ $topic['evidence_count'] }}</p>
                    @if($topic['evidence_count'])<label for="mastery-evidence-{{ $topic['id'] }}">{{ __('Evidence coverage') }}: {{ intdiv($topic['confidence_bp'],100) }}%</label><progress id="mastery-evidence-{{ $topic['id'] }}" max="10000" value="{{ $topic['confidence_bp'] }}"></progress>@endif
                </article>@endforeach</div>
            @else<p>{{ __('No current learners are allocated to this group.') }}</p>@endif
        </section>
    </div>
    <section class="mastery-panel" aria-labelledby="mastery-graph-heading"><h2 id="mastery-graph-heading">{{ __('Published prerequisite graph') }}</h2>
        @if($configuration['can_manage'])<form method="GET" action="{{ route('mastery.index') }}" class="mastery-form mastery-subject" aria-label="{{ __('Choose graph subject') }}">
            <input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}"><x-page.field type="select" :restore-old-input="false" name="subject_id" :label="__('Subject')" :options="array_column($subjects,'name','id')" :value="$graph['subject_id'] ?? null" /><button class="m3-button m3-button--tonal" type="submit">{{ __('Open subject graph') }}</button>
        </form>@endif
        @if($graph)<h3>{{ $graphSubjectName }}</h3><p>{{ __('Only published subject and assessment-term references appear. Removed sources remain in history and are hidden here.') }}</p>
            @php $graphNames=array_column($graph['nodes'],'name','id'); @endphp
            @if($configuration['can_manage'] && $writable)
                <form method="POST" action="{{ route('mastery.refresh',['subject'=>$graph['subject_id']]) }}">@csrf<button type="submit" class="m3-button m3-button--outlined">{{ __('Refresh published topic sources') }}</button></form>
                @php
                    $editLinks=old('links',$graph['links']);
                    $editLinks=is_array($editLinks) ? array_filter($editLinks,'is_array') : [];
                    $editLinks=array_map(fn($edge)=>array_map(fn($value)=>is_scalar($value) ? (string)$value : '',$edge),$editLinks);
                    $editRevision=old('expected_revision',$graph['settings']['revision']);
                    $editRevision=is_scalar($editRevision) ? (string)$editRevision : (string)$graph['settings']['revision'];
                    $editShared=old('family_marks_enabled',(int)$graph['settings']['family_marks_enabled']);
                    $editShared=is_scalar($editShared) ? (int)(string)$editShared : (int)$graph['settings']['family_marks_enabled'];
                @endphp
                <form method="POST" action="{{ route('mastery.save',['subject'=>$graph['subject_id']]) }}" class="mastery-form" data-mastery-graph aria-label="{{ __('Edit sourced prerequisites') }}">@csrf
                    <input type="hidden" name="expected_revision" value="{{ $editRevision }}"><input type="hidden" name="graph_present" value="1">
                    <x-page.field type="select" name="family_marks_enabled" :label="__('Share mastery from released marks across this school')" :options="['0'=>__('Disabled'),'1'=>__('Enabled')]" :restore-old-input="false" :value="$editShared" />
                    <p>{{ __('This setting applies to the whole school. Family views still require released evidence, current ownership and guardian approval.') }}</p>
                    <div data-mastery-edges>
                    @foreach($editLinks as $index=>$link)@include('growth.mastery-edge',['edgeIndex'=>$index,'edge'=>$link])@endforeach
                    </div>
                    <template data-mastery-edge-template>@include('growth.mastery-edge',['edgeIndex'=>'__INDEX__','edge'=>[]])</template>
                    <div class="mastery-actions"><button type="button" class="m3-button m3-button--outlined" data-mastery-add @disabled(!$graph['nodes'])>{{ __('Add prerequisite') }}</button><button class="m3-button m3-button--filled" type="submit">{{ __('Save sourced graph') }}</button></div>
                    <p>{{ __('Each edge points from a topic to its prerequisite. Supply a source citation; empty graphs are valid. Cycles and stale revisions are rejected.') }}</p>
                </form>
            @else
                @forelse($graph['links'] as $link)<article class="mastery-topic"><p>{{ $graphNames[$link['topic_id']] }} → {{ $graphNames[$link['prerequisite_id']] }}</p><p>{{ $link['source'] }}</p></article>@empty<p>{{ __('No prerequisites have been recorded.') }}</p>@endforelse
            @endif
        @else<p>{{ __('No published topics are available for this subject yet.') }}</p>@endif
    </section>
</div>
@endsection
@section('js')<script src="{{ asset('assets/js/mastery-workbench.js') }}?v={{ filemtime(public_path('assets/js/mastery-workbench.js')) }}" defer></script>@endsection
