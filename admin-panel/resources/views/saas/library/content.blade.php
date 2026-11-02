{{-- One library version's content, as a learner or reviewer would read it. --}}
@if ($content['type'] === 'question')
    <p><strong>{{ __('Question') }}:</strong> {{ $content['prompt'] }}</p>
    @if ($content['options'] !== [])
        <ul>@foreach ($content['options'] as $option)<li><code>{{ $option['id'] }}</code> {{ $option['text'] }}</li>@endforeach</ul>
    @endif
    <p><strong>{{ __('Accepted answers') }}:</strong> {{ implode(', ', $content['answers']) }} · {{ __(':seconds seconds', ['seconds' => $content['seconds']]) }}</p>
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
