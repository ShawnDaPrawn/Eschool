<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $paper['title'] }}</title>
<style>body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; } section { margin: 16pt 0; } .body { white-space: pre-wrap; } h1 { font-size: 18pt; } footer { margin-top: 20pt; border-top: 1pt solid currentColor; font-size: 9pt; }</style>
</head><body>
<h1>{{ $paper['title'] }}</h1>
<p>{{ $paper['paper_year'] }} · {{ $paper['paper_code'] }}@if($memo) · {{ __('Staff memorandum') }}@endif</p>
@foreach($paper['questions'] as $question)
<section><h2>{{ $question['number'] }} ({{ \App\Services\Academics\AcademicNumbers::decimal($question['max_units']) }})</h2>
<div class="body">{{ $question['prompt'] }}</div>
@if($memo)<div class="body">{{ $question['memo'] }}</div>@endif</section>
@endforeach
<footer>{{ $paper['licence_kind'] }} · {{ $paper['licence_reference'] }}<br>{{ __('Practice resource. This does not change official assessment marks.') }}</footer>
</body></html>
