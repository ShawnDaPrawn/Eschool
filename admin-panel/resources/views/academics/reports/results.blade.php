<article class="report-evidence">
    <h3>{{ $payload['learner_name'] }}</h3>
    <p>{{ $payload['homeroom_name'] }} · {{ $payload['year_name'] }} · {{ $payload['term_name'] ?? __('Annual report') }}</p>
    @if($payload['provisional'])<p>{{ __('Provisional running report') }}</p>@endif
    <div class="report-scroll" role="region" tabindex="0" aria-label="{{ __('Subject results for') }} {{ $payload['learner_name'] }}">
        <table class="report-results"><thead><tr><th scope="col">{{ __('Subject') }}</th><th scope="col">{{ __('Result') }}</th><th scope="col">{{ __('Rating') }}</th><th scope="col">{{ __('Coverage') }}</th><th scope="col">{{ __('Status') }}</th></tr></thead><tbody>
        @foreach($payload['subjects'] as $subject)<tr><th scope="row">{{ $subject['subject_name'] }}</th><td>{{ $subject['percentage_bp'] === null ? '—' : number_format($subject['percentage_bp'] / 100, 2).'%' }}</td><td>{{ $subject['rating_level'] ?? '—' }}</td><td>{{ number_format($subject['coverage_bp'] / 100, 2) }}%</td><td>{{ __(ucfirst(str_replace('_', ' ', $subject['result_status']))) }}</td></tr>@endforeach
        </tbody></table>
    </div>
    <p>{{ __('Captured days') }}: {{ $payload['attendance']['days_recorded'] }} · {{ __('Late') }}: {{ $payload['attendance']['late_days'] }} · {{ __('Excused') }}: {{ $payload['attendance']['excused_days'] }}</p>
</article>
