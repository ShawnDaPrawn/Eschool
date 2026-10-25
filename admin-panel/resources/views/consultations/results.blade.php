@extends('layouts.master')
@section('title', __('Consultation results'))
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/consultations.css') }}">@endsection
@section('content')
<div class="content-wrapper consultations" data-consultations-root><h1>{{ __('Consultation results') }}</h1>@include('consultations.header')
<p>{{ $result['closed']?__('Closed results'):__('Live aggregate counts') }} · {{ __('Responses recorded') }}: {{ $result['response_count'] }}</p><p>{{ $result['anonymous']?__('Anonymous responses'):__('Named responses') }}</p>
@foreach($result['questions'] as $question)<section class="m3-card"><div class="m3-card__content"><h2>{{ $question['label'] }}</h2><p>{{ __('Answered') }}: {{ $question['answered'] }} / {{ $result['response_count'] }}</p>
@if($question['kind']==='text')<p>{{ __('Individual text answers are available only to consultation managers after close.') }}</p>@else
@if($question['kind']==='multiple')<p>{{ __('Participants could select several choices; percentages need not total 100%.') }}</p>@endif
@foreach($question['options'] as $option)
@php
$count = collect($question['counts'])->firstWhere('option_id', $option['id'])['count'] ?? 0;
@endphp
<div class="consultation-result"><h3>{{ $option['label'] }}</h3><p>{{ $count }} / {{ $question['answered'] }} ({{ $question['answered']===0?0:round(100*$count/$question['answered'],1) }}%)</p><progress aria-label="{{ $option['label'] }}" value="{{ $count }}" max="{{ max(1,$question['answered']) }}"></progress></div>@endforeach
@endif</div></section>@endforeach
@if($responses!==null)<section class="m3-card"><div class="m3-card__content"><h2>{{ __('Closed survey responses') }}</h2><p>{{ __('Private individual answers are shown only in this authorized view.') }}</p>
@forelse($responses['items'] as $i=>$entry)<article><h3>{{ $entry['respondent_name']??__('Anonymous response') }} {{ $entry['respondent_name']===null?(($responses['page']-1)*50+$i+1):'' }}</h3>
@foreach($entry['answers'] as $answer)
@php
$question = collect($result['questions'])->firstWhere('id', $answer['question_id']);
@endphp
<h4>{{ $question['label']??__('Archived question') }}</h4>
@if($answer['text']!==null)<p class="consultation-prose">{{ $answer['text'] }}</p>@else<ul>@foreach($answer['option_ids'] as $option)<li>{{ collect($question['options']??[])->firstWhere('id',$option)['label']??__('Archived choice') }}</li>@endforeach</ul>@endif
@endforeach</article>@empty<p>{{ __('No responses were recorded.') }}</p>@endforelse
<nav aria-label="{{ __('Response pages') }}">@if($responses['page']>1)<a href="{{ route('consultations-panel.results',['consultation'=>$result['id'],'managed'=>1,'page'=>$responses['page']-1]) }}">{{ __('Previous page') }}</a>@endif
@if($responses['page']<$responses['last_page'])<a href="{{ route('consultations-panel.results',['consultation'=>$result['id'],'managed'=>1,'page'=>$responses['page']+1]) }}">{{ __('Next page') }}</a>@endif</nav>
</div></section>@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/consultations.js') }}" defer></script>@endsection
