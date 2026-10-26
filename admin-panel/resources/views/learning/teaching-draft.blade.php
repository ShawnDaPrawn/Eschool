<!doctype html><html lang="{{ $draft['request']['language'] }}"><head><meta charset="utf-8"><title>{{ __('Reviewed teaching material') }}</title><style>
body{font-family:'DejaVu Sans',sans-serif;font-size:11pt;line-height:1.5;color:#162838}h1{font-size:18pt}h2{font-size:14pt;margin-top:20pt}.text{white-space:pre-wrap}.note{font-size:10pt;color:#46566a}.question{page-break-inside:avoid;margin:16pt 0}
</style></head><body @if($draft['request']['language']==='ar') dir="rtl" @endif>
<p class="note">{{ __('Teacher reviewed material') }} · {{ __('Revision') }} {{ $draft['revision'] }}</p>
@if(isset($draft['definition']['title']))<h1>{{ $draft['definition']['title'] }}</h1>@endif
@if(isset($draft['definition']['sections']))
@foreach($draft['definition']['sections'] as $section)<h2>{{ __(ucfirst($section['level'])) }}</h2><div class="text">{{ $section['body'] }}</div>@endforeach
@elseif(isset($draft['definition']['text']))<div class="text">{{ $draft['definition']['text'] }}</div>
@elseif(isset($draft['definition']['questions']))
@foreach($draft['definition']['questions'] as $index=>$question)<section class="question"><h2>{{ __('Question') }} {{ $index+1 }}</h2><div class="text">{{ $question['definition']['prompt'] }}</div>
@foreach($question['definition']['options'] as $option)<p>{{ $option['id'] }}. {{ $option['text'] }}</p>@endforeach
@if($answers)<p><strong>{{ __('Teacher answer') }}:</strong> {{ implode(', ', $question['definition']['answers']) }}</p><div class="text">{{ $question['definition']['explanation'] }}</div>@endif</section>@endforeach
@elseif(isset($draft['definition']['question_marks']))<h1>{{ __('Reviewed script proposals') }}</h1><p class="note">{{ __('This copy contains proposals. The official marks record remains authoritative.') }}</p>
@foreach($draft['definition']['question_marks'] as $row)<section class="question"><h2>{{ __('Question') }} {{ collect($draft['question_labels'])->firstWhere('question_id', $row['question_id'])['number'] ?? __('Question') }}</h2><p>{{ $row['mark'] ?? __('Insufficient evidence') }} · {{ __('Confidence') }} {{ intdiv($row['confidence_bp'],100) }}%</p><div class="text">{{ $row['reason'] }}</div></section>@endforeach
@endif
</body></html>
