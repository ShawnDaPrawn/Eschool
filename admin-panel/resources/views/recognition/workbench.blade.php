@extends('layouts.master')
@section('title', __($module === 'house_points' ? 'Houses and points' : 'Awards'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/recognition.css') }}?v={{ filemtime(public_path('assets/css/recognition.css')) }}">@endsection
@section('content')
<div data-recognition-root class="content-wrapper m3 page-content recognition @if($assembly) recognition--assembly @endif">
<x-page.header :title="__($module === 'house_points' ? 'Houses and points' : 'Awards')" :subtitle="$module === 'house_points' ? __('Manage house membership, award points and retain reasoned corrections.') : __('Define achievements, nominate learners, record presentations and retain their history.')">
    @if($view==='editor')
    <x-slot:actions>
        <a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'configuration','session_year_id'=>$year]) }}">{{ __('Back to configuration') }}</a>
    </x-slot:actions>
    @endif
</x-page.header>
<nav class="panel-local-nav" aria-label="{{ __('Recognition workspace') }}">
@if($module==='awards')
<a href="{{ route('recognition.index',['module'=>$module,'view'=>'catalogue','session_year_id'=>$year]) }}" @if($view==='catalogue') aria-current="page" @endif>{{ __('Award catalogue') }}</a>
@endif
<a href="{{ route('recognition.index',['module'=>$module,'view'=>'learners','session_year_id'=>$year]) }}" @if($view==='learners') aria-current="page" @endif>{{ __('Learner records') }}</a>
@if($module==='awards')
<a href="{{ route('recognition.index',['module'=>$module,'view'=>'nominations','session_year_id'=>$year]) }}" @if($view==='nominations') aria-current="page" @endif>{{ __('Nominations and decisions') }}</a>
<a href="{{ route('recognition.index',['module'=>$module,'view'=>'assembly','session_year_id'=>$year]) }}" @if($view==='assembly') aria-current="page" @endif>{{ __('Assembly') }}</a>
@endif
@if($canConfigure)<a href="{{ route('recognition.index',['module'=>$module,'view'=>'configuration','session_year_id'=>$year]) }}" @if(in_array($view,['configuration','editor'],true)) aria-current="page" @endif>{{ __('Configuration') }}</a>@endif
</nav>
@if($messages)<div role="alert" class="recognition-warning"><ul>@foreach($messages as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
@if($draft)<details open class="m3-card"><summary>{{ __('Your unsaved recognition text') }}</summary>@foreach($draft as $key=>$value)<p><strong>{{ __(ucfirst(str_replace('_',' ',$key))) }}</strong>: {{ $value }}</p>@endforeach<p>{{ __('Compare the current record and enter the corrected change below.') }}</p></details>@endif
@if(!$canWrite)<p role="status">{{ __('This view is read only. Return to the current school year to make changes, or ask a school administrator about module access.') }}</p>@endif

@if($view==='catalogue' && $module==='awards')
@include('recognition.catalogue')
@elseif($view==='configuration')
@include('recognition.configuration-index')
@elseif($view==='editor')
@if($canWrite && $canConfigure)
@include('recognition.configuration')
@else<x-page.empty-state :title="__('Configuration is read only')" :message="__('Your current school role and selected school year determine whether these settings can be changed.')" />@endif
@elseif(in_array($view,['nominations','assembly'],true) && $module==='awards')
@include('recognition.assembly')
@else
@include('recognition.learner')
@endif
</div>
@endsection
@section('script')
<script src="{{ asset('assets/js/recognition-session.js') }}" defer></script>
@if($module==='house_points')<script src="{{ asset('assets/js/house-counters.js') }}" defer></script>@endif
@endsection
