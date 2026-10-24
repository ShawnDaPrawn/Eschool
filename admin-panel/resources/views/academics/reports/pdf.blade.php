<!doctype html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $design['heading'] }}</title>
<style>
body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #222; }
h1 { font-size: 19pt; } h2 { font-size: 13pt; margin-top: 20pt; }
table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid #888; padding: 6pt; text-align: left; }
.crest { max-width: 80pt; max-height: 80pt; } .provisional { font-weight: bold; border: 1px solid #888; padding: 8pt; }
.comment { white-space: pre-wrap; } .signature { margin-top: 18pt; } footer { margin-top: 22pt; font-size: 8pt; }
</style></head><body>
@if($design['show_crest'] && $design['crest'])<img src="{{ $design['crest'] }}" class="crest" alt="">@endif
<h1>{{ $card['school_name'] }}</h1><h2>{{ $design['heading'] }}</h2>
<p>{{ $card['learner_name'] }} · {{ $card['homeroom_name'] }} · {{ $card['year_name'] }} {{ $card['term_name'] ?? __('Annual report') }}</p>
@if($card['provisional'])<p class="provisional">{{ __('Provisional running report — incomplete results are not final term or year marks.') }}</p>@endif
<p class="comment">{{ $design['introduction'] }}</p>
@foreach($design['sections'] as $section)
    @if($section === 'subjects')
        <h2>{{ __('Subjects') }}</h2><table><thead><tr><th>{{ __('Subject') }}</th><th>{{ __('Percentage') }}</th><th>{{ __('Rating') }}</th><th>{{ __('Coverage') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @foreach($card['subjects'] as $subject)<tr><td>{{ $subject['subject_name'] }}</td><td>{{ $subject['percentage_bp'] === null ? '—' : intdiv($subject['percentage_bp'], 100).'.'.str_pad((string) ($subject['percentage_bp'] % 100), 2, '0', STR_PAD_LEFT).'%' }}</td><td>{{ $subject['rating_level'] ?? '—' }}</td><td>{{ intdiv($subject['coverage_bp'], 100).'.'.str_pad((string) ($subject['coverage_bp'] % 100), 2, '0', STR_PAD_LEFT) }}%</td><td>{{ (['complete' => __('Complete'), 'partial' => __('Partial'), 'ungraded' => __('Not yet graded'), 'excused' => __('Excused'), 'absent' => __('Absent'), 'catch_up' => __('Catch-up'), 'unavailable' => __('Assessment plan unavailable')])[$subject['result_status']] }}</td></tr>@endforeach
        </tbody></table>
    @elseif($section === 'attendance')
        <h2>{{ __('Attendance') }}</h2>
        <p>{{ __('Recorded days') }}: {{ $card['attendance']['days_recorded'] }} · {{ __('Present days') }}: {{ intdiv($card['attendance']['present_half_days'], 2) }}{{ $card['attendance']['present_half_days'] % 2 ? '.5' : '' }} · {{ __('Absent days') }}: {{ intdiv($card['attendance']['absent_half_days'], 2) }}{{ $card['attendance']['absent_half_days'] % 2 ? '.5' : '' }} · {{ __('Excused days') }}: {{ $card['attendance']['excused_days'] }} · {{ __('Late days') }}: {{ $card['attendance']['late_days'] }}</p>
        <p>{{ __('Uncaptured days are not counted as absences.') }}</p>
    @elseif($section === 'subject_comments')
        <h2>{{ __('Subject comments') }}</h2>@foreach($card['subjects'] as $subject)@if(!empty($comments['subject:'.$subject['subject_id']]))<h3>{{ $subject['subject_name'] }}</h3><p class="comment">{{ $comments['subject:'.$subject['subject_id']] }}</p>@endif @endforeach
    @elseif($section === 'homeroom_comment')
        <h2>{{ __('Homeroom comment') }}</h2><p class="comment">{{ $comments['homeroom'] ?? '' }}</p>
    @endif
@endforeach
@foreach(['grade_head' => __('Grade head'), 'principal' => __('Principal')] as $key => $label)@if(isset($signatures[$key]))<p class="signature">{{ $label }}: {{ $signatures[$key]['name'] }} · {{ $signatures[$key]['signed_at'] }}</p>@endif @endforeach
<footer class="comment">{{ $design['footer'] }}</footer>
</body></html>
