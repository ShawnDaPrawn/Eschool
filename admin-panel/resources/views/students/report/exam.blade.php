<style>
    .exam-report-body {
        overflow: hidden;
        max-height: 2000px;
        transition: max-height 0.3s ease;
    }

    .exam-report-body.collapsed {
        max-height: 0;
    }

    .exam-toggle-icon {
        transition: transform 0.3s ease;
        transform: rotate(0deg);
    }
    

    .exam-toggle-icon.rotated {
        transform: rotate(180deg);
    }

    .exam-table {
        table-layout: fixed;
        width: 100%;
    }

    .exam-table th {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.03em;
        color: var(--md-sys-color-on-surface-variant);
        font-weight: 600;
        white-space: nowrap;
    }

    .exam-table td,
    .exam-table th {
        vertical-align: middle;
    }
</style>
<div class="row">
    <div class="col-md-12">

        <div class="report-section-title">{{ __('exam_report') }}</div>
        @php
        // Group offline exam marks by their parent exam
        $offlineGroups = $examDetail->groupBy(fn($item) => $item->timetable?->exam?->id ?? 'na');

        // Group online exams by online_exam id (usually 1 subject per online exam, but grouping keeps it safe)
        $onlineGroups = collect($onlineExamDetail)->groupBy(fn($item) => $item['online_exam']->id ?? 'na');

        $hasAnyExam = $offlineGroups->count() || $onlineGroups->count();
        $examCounter = 0;
        $getGradeByPercentage = function ($percentage) use ($gradeRanges) {
        $range = ($gradeRanges ?? collect())->first(
        fn($g) => $percentage >= $g->starting_range && $percentage <= $g->ending_range
            );
            return $range->grade ?? '-';
            };
            @endphp

            @php
            // Group offline exam marks by their parent exam
            $offlineGroups = $examDetail->groupBy(fn($item) => $item->timetable?->exam?->id ?? 'na');

            // Group online exams by online_exam id (usually 1 subject per online exam, but grouping keeps it safe)
            $onlineGroups = collect($onlineExamDetail)->groupBy(fn($item) => $item['online_exam']->id ?? 'na');

            $hasAnyExam = $offlineGroups->count() || $onlineGroups->count();
            $examCounter = 0;
            @endphp

            @if($hasAnyExam)

            {{-- ================= OFFLINE EXAMS ================= --}}
            @if($offlineGroups->count())
            <h6 class="text-muted mb-2 mt-1">{{ __('offline_exams') }}</h6>
            @endif

            @foreach($offlineGroups as $group)
            @php
            $examCounter++;
            $examName = $group->first()->timetable?->exam?->name ?? __('n_a');
            $totalObtained = $group->sum(fn($m) => $m->obtained_marks ?? $m->marks ?? 0);
            $totalMax = $group->sum(fn($m) => $m->total_marks ?? 100);
            $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;
            $isPassed = $percentage >= 35;
            $totalGrade = $getGradeByPercentage($percentage);
            $collapseId = 'examCollapse' . $examCounter;
            @endphp
            <div class="card mb-3 exam-report-card">
                <div class="card-header d-flex justify-content-between align-items-center exam-report-toggle"
                    style="cursor:pointer;"
                    data-target="#{{ $collapseId }}">
                    <h6 class="mb-0">
                        <span class="text-primary">|</span>
                        {{ $examCounter }}. {{ $examName }} ({{ __('offline') }})
                    </h6>
                    <div class="d-flex align-items-center" style="gap: 2rem;">
                        <div class="text-center">
                            <div class="text-muted small">{{ __('marks') }}</div>
                            <strong>{{ $totalObtained }} / {{ $totalMax }}</strong>
                        </div>
                        <span class="badge {{ $isPassed ? 'badge-success' : 'badge-danger' }} p-2">
                            {{ $isPassed ? __('pass') : __('fail') }} ({{ $percentage }}%)
                        </span>
                        <i class="fa fa-chevron-down exam-toggle-icon"></i>
                    </div>
                </div>

                <div id="{{ $collapseId }}" class="exam-report-body collapsed">
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <colgroup>
                                <col style="width: 32%;">
                                <col style="width: 17%;">
                                <col style="width: 17%;">
                                <col style="width: 17%;">
                                <col style="width: 17%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>{{ __('subject') }}</th>
                                    <th class="text-center">{{ __('marks') }}</th>
                                    <th class="text-center">{{ __('obtained') }}</th>
                                    <th class="text-center">{{ __('grade') }}</th>
                                    <th class="text-center">{{ __('status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group as $mark)
                                @php
                                $obtained = $mark->obtained_marks ?? $mark->marks ?? 0;
                                $total = $mark->total_marks ?? 100;
                                $subjectPassed = isset($mark->passing_status)
                                ? (bool) $mark->passing_status
                                : ($obtained >= ($mark->passing_marks ?? $total * 0.35));
                                @endphp
                                <tr>
                                    <td>{{ $mark->subject?->name ?? $mark->subject_name ?? __('n_a') }}</td>
                                    <td class="text-center">{{ $total }}</td>
                                    <td class="text-center">{{ $obtained }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-primary p-2">{{ $mark->grade ?? '-' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $subjectPassed ? 'badge-success' : 'badge-danger' }} p-2">
                                            {{ $subjectPassed ? __('pass') : __('fail') }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                                <tr class="font-weight-bold">
                                    <td>{{ __('total') }}</td>
                                    <td class="text-center">{{ $totalMax }}</td>
                                    <td class="text-center">{{ $totalObtained }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-primary p-2">{{ $totalGrade }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $isPassed ? 'badge-success' : 'badge-danger' }} p-2">
                                            {{ $isPassed ? __('pass') : __('fail') }} ({{ $percentage }}%)
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach

            {{-- ================= ONLINE EXAMS ================= --}}
            @if($onlineGroups->count())
            <h6 class="text-muted mb-2 mt-4">{{ __('online_exams') }}</h6>
            @endif

            @foreach($onlineGroups as $group)
            @php
            $examCounter++;
            $examName = $group->first()['online_exam']->title ?? __('n_a');
            $totalObtained = $group->sum(fn($r) => $r['obtained_marks'] ?? 0);
            $totalMax = $group->sum(fn($r) => $r['total_marks'] ?? 0);
            $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;
            $isPassed = $percentage >= 35;
            $examGrade = $getGradeByPercentage($percentage);
            $collapseId = 'examCollapse' . $examCounter;
            @endphp

            <div class="card mb-3 exam-report-card">
                <div class="card-header d-flex justify-content-between align-items-center exam-report-toggle"
                    style="cursor:pointer;"
                    data-target="#{{ $collapseId }}">
                    <h6 class="mb-0">
                        <span class="text-primary">|</span>
                        {{ $examCounter }}. {{ $examName }} ({{ __('online') }})
                    </h6>
                    <div class="d-flex align-items-center" style="gap: 2rem;">
                        <div class="text-center">
                            <div class="text-muted small">{{ __('marks') }}</div>
                            <strong>{{ $totalObtained }} / {{ $totalMax }}</strong>
                        </div>
                        <span class="badge {{ $isPassed ? 'badge-success' : 'badge-danger' }} p-2">
                            {{ $isPassed ? __('pass') : __('fail') }} ({{ $percentage }}%)
                        </span>
                        <i class="fa fa-chevron-down exam-toggle-icon"></i>
                    </div>
                </div>

                <div id="{{ $collapseId }}" class="exam-report-body collapsed">
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <colgroup>
                                <col style="width: 32%;">
                                <col style="width: 17%;">
                                <col style="width: 17%;">
                                <col style="width: 17%;">
                                <col style="width: 17%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>{{ __('exam') }}</th>
                                    <th class="text-center">{{ __('marks') }}</th>
                                    <th class="text-center">{{ __('obtained') }}</th>
                                    <th class="text-center">{{ __('grade') }}</th>
                                    <th class="text-center">{{ __('status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $examName }}</td>
                                    <td class="text-center">{{ $totalMax }}</td>
                                    <td class="text-center">{{ $totalObtained }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-primary p-2">{{ $examGrade }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $isPassed ? 'badge-success' : 'badge-danger' }} p-2">
                                            {{ $isPassed ? __('pass') : __('fail') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endforeach

            @else

            <div class="report-empty-state">
                <h6 class="text-muted">{{ __('no_exam_records_found') }}</h6>
            </div>

            @endif

    </div>
</div>

<style>
    .exam-report-body {
        overflow: hidden;
        max-height: 2000px;
        transition: max-height 0.3s ease;
    }

    .exam-report-body.collapsed {
        max-height: 0;
    }

    .exam-toggle-icon {
        transition: transform 0.3s ease;
        transform: rotate(0deg);
    }

    .exam-toggle-icon.rotated {
        transform: rotate(180deg);
    }
</style>
