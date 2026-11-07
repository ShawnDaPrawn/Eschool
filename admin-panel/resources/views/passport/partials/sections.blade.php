{{-- The learner passport's sections, shared by the panel page and the learner's
     PDF. Plain, semantic markup: dompdf draws it as it is. $passport comes from
     App\Services\Passport\LearnerPassport::build(). --}}
@php($percent = fn (?int $bp) => $bp === null ? null : \App\Services\Academics\AcademicNumbers::decimal($bp).'%')
<section aria-labelledby="passport-schools" data-passport-section="schools">
<h2 id="passport-schools">{{ __('Schools') }}</h2>
@forelse($passport['schools'] as $index => $school)
<div class="passport-school" style="margin-block:12px">
<h3 id="passport-school-{{ $index }}">{{ $school['name'] }}@if($school['current']) · {{ __('Current school') }}@endif</h3>
@if($school['attended_from'])
<p>{{ $school['attended_to'] ? __('Attended from :from to :to', ['from' => $school['attended_from'], 'to' => $school['attended_to']]) : __('Attending since :from', ['from' => $school['attended_from']]) }}</p>
@endif
<h4>{{ __('Awards') }}</h4>
@forelse($school['awards'] as $award)
<p><strong>{{ $award['title'] }}</strong> · {{ $award['presented_on'] }}@if($award['kind'] === 'badge') · {{ __('Badge') }}@endif @if($award['outside_organisation']) · {{ __('From :organisation', ['organisation' => $award['outside_organisation']]) }}@endif @if($award['event']) · {{ $award['event'] }}@endif<br><span style="white-space:pre-wrap">{{ $award['criteria'] }}</span></p>
@empty
<p>{{ __('No awards recorded here yet.') }}</p>
@endforelse
<h4>{{ __('Activities') }}</h4>
@forelse($school['activities'] as $activity)
<p>{{ $activity['name'] }} · {{ $activity['discipline'] }} · {{ $activity['season'] }}@if($activity['school_year']) · {{ $activity['school_year'] }}@endif @if($activity['role'] === 'captain') · {{ __('Captain') }}@endif<br>{{ __(':from to :to', ['from' => $activity['starts_on'], 'to' => $activity['ends_on']]) }}</p>
@empty
<p>{{ __('No activities recorded here yet.') }}</p>
@endforelse
<h4>{{ __('Service') }}</h4>
@if($school['service'])
<p>{{ __(':minutes minutes of supervised peer tutoring in :sessions sessions', ['minutes' => $school['service']['minutes'], 'sessions' => $school['service']['sessions']]) }}</p>
@else
<p>{{ __('No service minutes recorded here yet.') }}</p>
@endif
</div>
@empty
<p>{{ __('No school records yet.') }}</p>
@endforelse
</section>
<section aria-labelledby="passport-transfers" data-passport-section="transfers">
<h2 id="passport-transfers">{{ __('Results carried with a transfer') }}</h2>
@forelse($passport['transfers'] as $index => $transfer)
<div style="margin-block:12px">
<h3 id="passport-transfer-{{ $index }}">{{ __('From :from to :to', ['from' => $transfer['from_school_name'], 'to' => $transfer['to_school_name']]) }}</h3>
<p>{{ __('Last day at :school was :date', ['school' => $transfer['from_school_name'], 'date' => $transfer['last_attended_on']]) }} · {{ __('Started on :date', ['date' => $transfer['starts_on']]) }}</p>
@if($transfer['reports'] !== [])
<p>{{ __('Report cards') }}: @foreach($transfer['reports'] as $report){{ $report['year_name'] }} · {{ $report['term_name'] }}@if(! $loop->last); @endif @endforeach</p>
@endif
@if($transfer['results'] !== [])
<table class="passport-results" style="border-collapse:collapse;width:100%">
<caption style="text-align:start">{{ __('Released results from the sending school') }}</caption>
<thead><tr><th scope="col" style="text-align:start">{{ __('Subject') }}</th><th scope="col" style="text-align:start">{{ __('Result') }}</th><th scope="col" style="text-align:start">{{ __('Kind') }}</th><th scope="col" style="text-align:start">{{ __('Released') }}</th></tr></thead>
<tbody>@foreach($transfer['results'] as $result)<tr><td>{{ $result['subject_name'] }}</td><td>{{ $percent($result['percentage_bp']) ?? '—' }}@if($result['rating_level'] !== null) · {{ __('Level :level', ['level' => $result['rating_level']]) }}@endif</td><td>{{ $result['mode'] === 'running' ? __('Result so far') : __('Final result') }}</td><td>{{ $result['released_on'] ?? '—' }}</td></tr>@endforeach</tbody>
</table>
@else
<p>{{ __('No released results came with this transfer.') }}</p>
@endif
</div>
@empty
<p>{{ __('No results have arrived with a transfer.') }}</p>
@endforelse
</section>
<section aria-labelledby="passport-reading" data-passport-section="reading">
<h2 id="passport-reading">{{ __('Reading') }}</h2>
@forelse($passport['reading']['items'] as $book)
<p>{{ $book['title'] }}@if($book['author'] ?? null) · {{ $book['author'] }}@endif @if($book['finished_on'] ?? null) · {{ $book['finished_on'] }}@endif</p>
@empty
<p>{{ __('Books read will appear here once the school library is in use.') }}</p>
@endforelse
</section>
