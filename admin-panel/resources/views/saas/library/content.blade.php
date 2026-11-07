{{-- One library version's content, as a learner or reviewer would read it. --}}
@if ($content['type'] === 'question')
    <p><strong>{{ __('Question') }}:</strong> {{ $content['prompt'] }}</p>
    @if ($content['options'] !== [])
        <ul>@foreach ($content['options'] as $option)<li><code>{{ $option['id'] }}</code> {{ $option['text'] }}</li>@endforeach</ul>
    @endif
    @if (($content['steps'] ?? []) !== [])
        <ol>@foreach ($content['steps'] as $step)<li>{{ $step['prompt'] }} → <strong>{{ implode(', ', $step['answers']) }}</strong>@if ($step['options'] !== []) ({{ collect($step['options'])->map(fn ($o) => $o['id'].': '.$o['text'])->implode('; ') }})@endif</li>@endforeach</ol>
    @endif
    @if (($content['diagram'] ?? null) !== null)
        <p><img src="{{ route('saas.library.diagrams.show', $content['diagram']['id']) }}" alt="{{ __('Diagram for this question') }}" width="{{ min(480, $content['diagram']['width']) }}" loading="lazy"></p>
        <ul>@foreach ($content['regions'] as $region)<li><code>{{ $region['id'] }}</code> ({{ $region['x'] }}%, {{ $region['y'] }}%) → {{ collect($content['labels'])->firstWhere('id', $content['mapping'][$region['id']] ?? '')['text'] ?? '' }}</li>@endforeach</ul>
    @endif
    @if (($content['max_seconds'] ?? null) !== null)
        <p>{{ __('Spoken answer of up to :seconds seconds, marked by the learner\'s teacher.', ['seconds' => $content['max_seconds']]) }}</p>
    @endif
    <p><strong>{{ __('Answer type') }}:</strong> {{ $content['kind_label'] ?? '' }}@if ($content['answers'] !== []) · <strong>{{ __('Accepted answers') }}:</strong> {{ implode(', ', $content['answers']) }}@endif · {{ __(':seconds seconds', ['seconds' => $content['seconds']]) }}</p>
    <p><strong>{{ __('Worked explanation') }}:</strong> {{ $content['explanation'] }}</p>
    @if ($content['paper'])
        <p>{{ __('Past paper :code (:year), question :question', ['code' => $content['paper']['code'], 'year' => $content['paper']['year'], 'question' => $content['paper']['question']]) }}</p>
    @endif
@elseif ($content['type'] === 'lesson')
    <div>{!! nl2br(e($content['body'])) !!}</div>
@else
    @if ($content['instructions'] !== '')<p>{{ $content['instructions'] }}</p>@endif
    <ul>@foreach ($content['items'] as $title)<li>{{ $title }}</li>@endforeach</ul>
@endif
