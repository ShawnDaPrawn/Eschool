@extends('layouts.master')
@section('title', __('Configuration history'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/consultations.css') }}">@endsection
@section('content')
<div class="content-wrapper consultations" data-consultations-root><h1>{{ __('Configuration history') }}</h1>@include('consultations.header')<a href="{{ route('consultations-panel.edit',['consultation'=>$id]) }}">{{ __('Review consultation configuration') }}</a>
<ul>@foreach($history['items'] as $change)<li><a href="{{ route('consultations-panel.history',['consultation'=>$id,'event'=>$change['id'],'history_page'=>$history['page']]) }}">{{ __(ucfirst(str_replace('_',' ',$change['action']))) }} · {{ __('Revision') }} {{ $change['revision'] }}</a> · {{ $change['actor_name']??__('School scheduler') }} · {{ \Carbon\CarbonImmutable::parse($change['created_at'])->setTimezone($access['timezone'])->format('Y-m-d H:i') }}</li>@endforeach</ul>
<nav aria-label="{{ __('Configuration history pages') }}">@if($history['page']>1)<a href="{{ route('consultations-panel.history',['consultation'=>$id,'history_page'=>$history['page']-1]) }}">{{ __('Previous page') }}</a>@endif
@if($history['page']<$history['last_page'])<a href="{{ route('consultations-panel.history',['consultation'=>$id,'history_page'=>$history['page']+1]) }}">{{ __('Next page') }}</a>@endif</nav>
@if($entry)<article class="m3-card"><div class="m3-card__content"><h2>{{ $entry['snapshot']['title'] }}</h2><p>{{ __('Revision') }} {{ $entry['revision'] }} · {{ __(ucfirst($entry['snapshot']['status'])) }}</p><p>{{ $entry['snapshot']['anonymous']?__('Anonymous responses'):__('Named responses') }}</p><p class="consultation-prose">{{ $entry['snapshot']['instructions']??'' }}</p><p>{{ __('Audience') }}: {{ __(ucfirst(str_replace('_',' ',$entry['snapshot']['audience']['kind']))) }}</p><p>{{ __('Opens') }}: {{ $entry['snapshot']['opens_at'] }} · {{ __('Closes') }}: {{ $entry['snapshot']['closes_at'] }}</p>
@if($entry['snapshot']['reason'])<p class="consultation-prose">{{ $entry['snapshot']['reason'] }}</p>@endif
@foreach($entry['snapshot']['questions'] as $question)<h3>{{ $question['label'] }}</h3><ul>@foreach($question['options'] as $option)<li>{{ $option['label'] }} @if($option['statement'])· {{ $option['statement'] }}@endif</li>@endforeach</ul>@endforeach
</div></article>@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/consultations.js') }}" defer></script>@endsection
