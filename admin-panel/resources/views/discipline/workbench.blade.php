@extends('layouts.master')
@section('title', __('School discipline'))
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/progress-notes.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/discipline.css') }}">
@endsection
@section('content')
<div class="content-wrapper m3 page-content progress-notes discipline-workbench" @if($hasDraft) data-unsaved-draft @endif>
<x-page.header :title="$view==='attendance' ? __('Detention attendance') : __('School discipline')" :subtitle="__('Record facts, apply the school framework and retain reasoned reviews. Principal hearings are decisions, never automatic expulsions.')" />
@if($draft)<section class="note-card" aria-label="{{ __('Your unsaved discipline draft') }}"><h2>{{ __('Your unsaved draft') }}</h2><p>{{ __('Compare your draft with the current record below. Private drafts are not stored in a shared session.') }}</p>@foreach($draft as $field=>$value)<p><strong>{{ __(ucfirst(str_replace('_',' ',$field))) }}</strong></p><pre>{{ $value }}</pre>@endforeach</section>@endif
@if($messages)<div role="alert" class="m3-banner m3-banner--warning"><ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if(session('success'))<p role="status" class="m3-banner m3-banner--success">{{ session('success') }}</p>@endif
<nav class="panel-local-nav" aria-label="{{ __('Discipline workspace') }}">
@foreach(['list'=>['records','Discipline records'],'detentions'=>['detentions','Detention sessions'],'configuration'=>['framework','Configuration'],'attendance'=>['detentions','Attendance']] as $key=>$destination)
<a href="{{ route('discipline-panel.index',['mode'=>$destination[0],'view'=>$key,'session_year_id'=>$year]) }}" @if($view===$key) aria-current="page" @endif>{{ __($destination[1]) }}</a>
@endforeach
</nav>
<p class="panel-record-context">{{ __('School year') }}: {{ collect($config['years'])->firstWhere('id',$year)['name'] ?? $year }}</p>
@if($view==='configuration')
@include('discipline.configuration')
@elseif(in_array($view,['list','create','record'],true))
@if($view==='list')
@if($canWrite && $year===$config['session_year_id'])<div class="panel-actions"><a class="m3-button m3-button--filled" href="{{ route('discipline-panel.index',['mode'=>'records','view'=>'create','session_year_id'=>$year]) }}">{{ __('Record occurrence') }}</a></div>@endif
@include('discipline.records')
@elseif($view==='create')
@include('discipline.learner-search')
@include('discipline.record')
@else
<div class="panel-actions"><a class="m3-button m3-button--outlined" href="{{ route('discipline-panel.index',['mode'=>'records','view'=>'list','session_year_id'=>$year]) }}">{{ __('Back to discipline records') }}</a></div>
@include('discipline.record')
@endif
@else
@if($view==='detentions' || ($view==='attendance' && !$detention))
@if($view==='detentions' && ($config['can_manage'] || $config['grade_ids']) && $canWrite && $year===$config['session_year_id'])<div class="panel-actions"><a class="m3-button m3-button--filled" href="{{ route('discipline-panel.index',['mode'=>'detentions','view'=>'detention-create','session_year_id'=>$year]) }}">{{ __('Plan supervised detention') }}</a></div>@endif
@include('discipline.detentions')
@else
<div class="panel-actions"><a class="m3-button m3-button--outlined" href="{{ route('discipline-panel.index',['mode'=>'detentions','view'=>$view==='attendance'?'attendance':'detentions','session_year_id'=>$year]) }}">{{ __('Back to sessions') }}</a></div>
@if($view==='attendance' && $detention && $detention['on_date']>$config['today'])<p role="status" class="m3-banner m3-banner--info">{{ __('This is a future session. Attendance opens on its scheduled date.') }}</p>@endif
@include('discipline.detention')
@endif
@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/discipline-workbench.js') }}" defer></script>@endsection
