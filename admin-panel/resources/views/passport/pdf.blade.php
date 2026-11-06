<!doctype html><html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head><meta charset="utf-8"><title>{{ __('Learner passport') }}</title><style>
body{font-family:'DejaVu Sans',sans-serif;font-size:10.5pt;line-height:1.45;color:#162838}h1{font-size:18pt;margin-bottom:4pt}h2{font-size:14pt;margin-top:18pt}h3{font-size:12pt;margin-top:12pt}h4{font-size:11pt;margin:8pt 0 2pt}.note{font-size:9pt;color:#46566a}th,td{border-bottom:1px solid #c9d1da;padding:3pt 4pt;vertical-align:top}
</style></head><body>
<h1>{{ $passport['learner']['name'] }}</h1>
@if($passport['learner']['primary_identifier'])
<p>{{ $passport['learner']['primary_identifier']['type'] === 'lurits' ? __('LURITS number') : __('NavigateEd number (temporary)') }}: {{ $passport['learner']['primary_identifier']['value'] }}</p>
@endif
<p class="note">{{ __('Learner passport') }} · {{ __('Downloaded on :date', ['date' => substr($generated, 0, 10)]) }}</p>
<p class="note">{{ __('Your records from the NavigateEd schools you attended. Health, counselling, wellbeing, discipline, support plans and safeguarding records are never part of a passport.') }}</p>
@include('passport.partials.sections', ['passport' => $passport])
</body></html>
