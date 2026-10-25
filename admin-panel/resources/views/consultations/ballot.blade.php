@extends('layouts.master')
@section('title', $ballot['title'])
@section('css')<link rel="stylesheet" href="{{ asset('assets/css/consultations.css') }}">@endsection
@section('content')
<div class="content-wrapper consultations" data-consultations-root><h1>{{ $ballot['title'] }}</h1>@include('consultations.header')
<p class="consultation-prose">{{ $ballot['instructions'] }}</p><p>{{ $ballot['anonymous']?__('Anonymous responses'):__('Named responses') }} · {{ __('One response per person') }}</p>
@if($ballot['participated'])<p role="status">{{ __('Your response is recorded. You can participate only once.') }}</p>
@elseif(!$ballot['open'])<p role="status">{{ __('This consultation is not open for responses.') }}</p>
@elseif($canWrite && $ballot['writable'])
<form method="POST" action="{{ route('consultations-panel.vote',['consultation'=>$ballot['id']]) }}" aria-label="{{ __('Cast your response') }}">@include('consultations.hidden')
@foreach($ballot['questions'] as $index=>$question)<fieldset><legend>{{ $question['label'] }} @if($question['required'])({{ __('Required') }})@endif</legend><input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question['id'] }}">
@if($question['kind']==='text')<label for="answer-{{ $question['id'] }}">{{ __('Your answer') }}<textarea id="answer-{{ $question['id'] }}" name="answers[{{ $index }}][text]" maxlength="2000" @required($question['required'])>{{ $answers[$question['id']]['text']??'' }}</textarea></label>
@else
@if($question['kind']==='multiple')<p>{{ __('Maximum choices') }}: {{ $question['max_choices'] }}</p>@endif
@foreach($question['options'] as $option)<label class="consultation-check" for="answer-{{ $question['id'] }}-{{ $option['id'] }}"><input id="answer-{{ $question['id'] }}-{{ $option['id'] }}" type="{{ $question['kind']==='single'?'radio':'checkbox' }}" name="answers[{{ $index }}][option_ids][]" value="{{ $option['id'] }}" @checked(in_array($option['id'],$answers[$question['id']]['option_ids']??[],false)) @required($question['kind']==='single'&&$question['required'])>{{ $option['label'] }}</label>
@if($option['statement'])<p class="consultation-prose">{{ $option['statement'] }}</p>@endif
@endforeach
@endif
</fieldset>@endforeach
<p>{{ __('Review your answers before submitting. A recorded response cannot be changed.') }}</p><button type="submit" class="m3-button m3-button--filled">{{ __('Record my response') }}</button></form>
@endif
@if($ballot['status']==='closed'||$ballot['results_visibility']==='public_live'||\Carbon\CarbonImmutable::parse($ballot['closes_at'])->isPast())<a href="{{ route('consultations-panel.results',['consultation'=>$ballot['id']]) }}">{{ __('View consultation results') }}</a>@endif
</div>
@endsection
@section('script')<script src="{{ asset('assets/js/consultations.js') }}" defer></script>@endsection
