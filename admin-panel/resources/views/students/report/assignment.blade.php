@php
    $mappedAssignments = $Assignment->map(function ($submission) {
        $dueDate = $submission->assignment?->due_date;

        if ($submission->status == 1) {
            $status = 'Accepted';
        } elseif ($submission->status == 2) {
            $status = 'Rejected';
        } elseif ($dueDate && now()->gt(\Carbon\Carbon::parse($dueDate))) {
            $status = 'Overdue';
        } else {
            $status = 'Pending';
        }

        $fileRecord = $submission->file->first();
        $fileUrl = $fileRecord && !empty($fileRecord->file_url) ? $fileRecord->file_url : '';

        $semesterId = $submission->assignment?->semester_id ? (int) $submission->assignment->semester_id : null;
        $semesterName = $submission->assignment?->semester?->name;

        return [
            'id' => (int) $submission->id,
            'assignmentName' => $submission->assignment?->name ?? 'N/A',
            'subjectName' => $submission->assignment?->subject?->name ?? 'N/A',
            'teacherName' =>
                $submission->assignment?->class_section?->subject_teachers->first()?->teacher?->user?->full_name ??
                'N/A',
            'sessionYearId' => (int) ($submission->session_year_id ?? 0),
            'semesterId' => $semesterId,
            'semesterLabel' => $semesterName ?? 'No Semester',
            'dueDate' => $submission->assignment?->due_date
                ? date('Y-m-d H:i', strtotime($submission->assignment->due_date))
                : '',
            'submittedAt' => $submission->created_at ? date('Y-m-d H:i', strtotime($submission->created_at)) : '',
            'points' => (int) ($submission->points ?? 0),
            'maxPoints' => (int) ($submission->assignment?->points ?? 100),
            'status' => $status,
            'feedback' => $submission->feedback ?? '',
            'attachedFile' => $fileUrl,
            'submissionText' => $submission->text_submission ?? '',
            'submissionLink' => $submission->link ?? '',
        ];
    });
@endphp
<style>
    #review-submission-modal .text-secondary,
    #review-submission-modal .text-muted {
        color: #374151 !important;
    }
</style>
<div id="assignments-tab-content-wrapper">
    <div id="assignments-tab-content" class="d-block">

        {{-- ── Header & Filters ── --}}
        <div class="row align-items-center mb-4 report-divider-bottom pb-3">
            <div class="col-md-6">
                <h4 class="report-heading mb-1">{{ __('student_assignment_report') }}</h4>
                <p class="small text-muted mb-0">{{ __('student_assignment_report_hint') }}</p>
            </div>
            <div class="col-md-6 d-flex flex-wrap justify-content-md-end mt-3 mt-md-0" style="gap: 10px;">

                {{-- Semester Filter --}}
                <div class="mb-2">
                    <label class="d-block font-weight-bold text-muted text-uppercase mb-1"
                        style="font-size: 10px; letter-spacing: 0.5px;">{{ __('semester') }}</label>

                    <select id="semester-filter" onchange="applyFilters()" class="form-control form-control-sm"
                        style="min-width: 150px;">
                        <option value="all">{{ __('all_semesters') }}</option>
                        @foreach ($semesters as $semester)
                            <option value="{{ $semester->id }}">
                                {{ $semester->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status Filter --}}
                <div class="mb-2">
                    <label class="d-block font-weight-bold text-muted text-uppercase mb-1"
                        style="font-size: 10px; letter-spacing: 0.5px;">{{ __('status') }}</label>
                    <select id="status-filter" onchange="applyFilters()" class="form-control form-control-sm"
                        style="min-width: 150px;">
                        <option value="all">{{ __('all_statuses') }}</option>
                        <option value="Accepted">{{ __('accepted_graded') }}</option>
                        <option value="Pending">{{ __('pending_review') }}</option>
                        <option value="Overdue">{{ __('overdue') }}</option>
                        <option value="Rejected">{{ __('rejected') }}</option>
                        <option value="Late">{{ __('late_submitted') }}</option>
                    </select>
                </div>

            </div>
        </div>

        {{-- ── Statistics ── --}}
        <div class="report-stat-strip">
            <div class="report-stat-item accent-theme">
                <div class="report-stat-label">{{ __('overall_points_ratio') }}</div>
                <div class="report-stat-value">
                    <span id="stat-total-obtained">0</span>
                    <span class="text-muted small">/</span>
                    <span class="text-muted small" id="stat-total-max">0</span>
                </div>
                <div class="report-stat-sub" id="stat-percentage" style="color: var(--md-sys-color-primary);">
                    0% {{ __('grade_average') }}
                </div>
            </div>
            <div class="report-stat-item accent-success">
                <div class="report-stat-label">{{ __('accepted') }}</div>
                <div class="report-stat-value" id="stat-accepted-count">
                    0 <span class="small text-muted font-weight-normal">{{ __('graded') }}</span>
                </div>
                <div class="report-stat-sub" style="color:#166534;">{{ __('approved_submissions') }}</div>
            </div>
            <div class="report-stat-item accent-info">
                <div class="report-stat-label">{{ __('pending_review') }}</div>
                <div class="report-stat-value" id="stat-pending-count">
                    0 <span class="small text-muted font-weight-normal">{{ __('awaiting') }}</span>
                </div>
                <div class="report-stat-sub" style="color:#155e75;">{{ __('awaiting_score_input') }}</div>
            </div>
            <div class="report-stat-item accent-danger">
                <div class="report-stat-label">{{ __('overdue_and_rejected') }}</div>
                <div class="report-stat-value" id="stat-overdue-count">
                    0 <span class="small text-muted font-weight-normal">{{ __('items') }}</span>
                </div>
                <div class="report-stat-sub" id="stat-overdue-subtitle" style="color:#b42318;">
                    {{ __('requires_submission_attention') }}
                </div>
            </div>
        </div>

        {{-- ── Progress Tracker ── --}}
        <div class="report-tracker">
            <div class="d-flex justify-content-between align-items-center small text-muted mb-2 font-weight-medium">
                <span>{{ __('task_completion_tracker') }}</span>
                <span id="tracker-ratio-label" class="font-weight-bold text-dark">0 / 0 {{ __('completed_submitted') }}
                    (0%)</span>
            </div>
            <div class="progress" style="height: 10px;" id="multi-progress-bar"></div>
            <div class="d-flex flex-wrap align-items-center mt-3 report-tracker-legend" style="gap: 15px;">
                <span class="d-flex align-items-center">
                    <span class="report-tracker-dot bg-success mr-2"></span>{{ __('accepted') }}
                </span>
                <span class="d-flex align-items-center">
                    <span class="report-tracker-dot bg-warning mr-2"></span>{{ __('late_submitted') }}
                </span>
                <span class="d-flex align-items-center">
                    <span class="report-tracker-dot bg-primary mr-2"></span>{{ __('pending_review') }}
                </span>
                <span class="d-flex align-items-center">
                    <span class="report-tracker-dot bg-danger mr-2"></span>{{ __('overdue') }}
                </span>
                <span class="d-flex align-items-center">
                    <span class="report-tracker-dot bg-secondary mr-2"></span>{{ __('rejected') }}
                </span>
            </div>
        </div>


        <div class="d-block" id="subject-report-container"></div>

    </div>


    <div id="review-submission-modal" class="modal custom-modal-backdrop">
        <div class="modal-dialog" style="margin-top: 0; max-width: 800px; width: 100%;">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="modal-eval-description">
                            {{ __('submission_details_review') }}
                        </h5>
                        <p class="small mb-0 text-muted mt-1">{{ __('reviewing_assignment_status_and_feedback') }}</p>
                    </div>
                    <button type="button" onclick="closeReviewModal()" class="close" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 0;">
                    <div class="row no-gutters">
                        {{-- Left --}}
                        <div class="col-md-7 p-4 bg-light border-right">
                            <div class="report-modal-box p-3 mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="report-modal-avatar mr-3" id="modal-student-avatar">S</div>
                                    <div>
                                        <h6 class="font-weight-bold text-dark mb-0" id="modal-student-name">
                                            {{ __('student') }}</h6>
                                        <div class="small text-muted mt-1">
                                            <span id="modal-subject-label">{{ __('subject') }}</span>
                                            <span class="mx-1">|</span>
                                            <span id="modal-submitted-time">{{ __('submitted_date') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="d-block font-weight-bold text-muted text-uppercase mb-2"
                                    style="font-size:10px;letter-spacing:0.5px;">{{ __('submission_text') }}</label>
                                <div class="report-modal-box p-3 small text-secondary" id="modal-submission-text"
                                    style="min-height: 50px; max-height: 180px; overflow-y: auto; overflow-x: hidden; white-space: pre-wrap; word-break: break-word;">
                                    —</div>
                            </div>
                            <div class="mb-3" id="modal-file-section">
                                <label class="d-block font-weight-bold text-muted text-uppercase mb-2"
                                    style="font-size:10px;letter-spacing:0.5px;">{{ __('attached_file') }}</label>
                                <div class="report-modal-box p-3 d-flex flex-row flex-wrap align-items-center justify-content-between"
                                    style="gap: 10px;" id="modal-file-box">
                                    <div class="d-flex align-items-center" style="min-width: 0; flex: 1 1 200px;">
                                        <div class="badge badge-danger p-2 mr-3 font-weight-bold flex-shrink-0"
                                            style="font-size:0.8rem;">{{ __('files') }}</div>
                                        <div style="min-width: 0;">
                                            <p class="font-weight-bold text-dark mb-0 small text-truncate"
                                                id="modal-filename-text" style="max-width: 100%;">
                                                {{ __('no_file_uploaded') }}</p>
                                            <p class="small text-muted mb-0" style="font-size:10px;">
                                                {{ __('uploaded_document') }}</p>
                                        </div>
                                    </div>
                                    <a href="#" id="modal-download-btn" download rel="noopener noreferrer"
                                        class="btn btn-secondary btn-sm d-none flex-shrink-0">{{ __('download') }}</a>
                                </div>
                            </div>

                            <div>
                                <label class="d-block font-weight-bold text-muted text-uppercase mb-2"
                                    style="font-size:10px;letter-spacing:0.5px;">{{ __('submission_link') }}</label>
                                <div class="report-modal-box p-3 text-secondary small" id="modal-link-box">
                                    {{ __('no_link_provided') }}</div>
                            </div>
                        </div>
                        {{-- Right --}}
                        <div class="col-md-5 p-4 bg-white">
                            <div class="mb-4">
                                <label class="d-block font-weight-bold text-muted text-uppercase mb-2"
                                    style="font-size:10px;letter-spacing:0.5px;">{{ __('submission_status') }}</label>
                                <div id="modal-status-badge-container"></div>
                            </div>
                            <div class="mb-4">
                                <label class="d-block font-weight-bold text-muted text-uppercase mb-2"
                                    style="font-size:10px;letter-spacing:0.5px;">{{ __('grade_points') }}</label>
                                <div
                                    class="report-modal-box bg-soft p-3 d-flex flex-row align-items-baseline justify-content-between">
                                    <div>
                                        <span class="h3 font-weight-bold text-dark mb-0"
                                            id="modal-points-obtained-val">0</span>
                                        <span class="text-muted small mx-1">/</span>
                                        <span class="text-muted font-weight-bold" id="modal-points-max-val">0</span>
                                    </div>
                                    <span class="badge badge-primary font-weight-bold px-2 py-1 ml-auto"
                                        id="modal-points-percentage">0%</span>
                                </div>
                            </div>
                            <div>
                                <label class="d-block font-weight-bold text-muted text-uppercase mb-2"
                                    style="font-size:10px;letter-spacing:0.5px;">{{ __('teachers_feedback') }}</label>
                                <div class="report-modal-box bg-soft p-3 text-secondary small"
                                    style="min-height:90px;border-left:3px solid var(--md-sys-color-primary);">
                                    <p id="modal-feedback-text" class="font-italic mb-0">
                                        {{ __('no_feedback_provided') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeReviewModal()"
                        class="btn btn-secondary btn-sm">{{ __('close_details') }}</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    (function() {

        const assignmentSubmissions = @json($mappedAssignments);

        // Local helpers: this partial's script runs inline, before custom/function.js is loaded.
        // Submission text/link and the assignment/subject/teacher names are user supplied.
        function esc(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/`/g, '&#96;');
        }

        // Only http(s) links are opened/linked; anything else (javascript:, data:, ...) becomes '#'.
        function httpUrl(value) {
            const str = String(value === null || value === undefined ? '' : value).trim();
            return /^https?:\/\//i.test(str.replace(/[\t\n\r]/g, '')) ? str : '#';
        }

        // Force both dropdowns to "all" on every fresh tab load.
        // This prevents any stale URL value or browser state from
        // silently filtering the data when no filter was intentionally applied.
        document.getElementById('semester-filter').value = 'all';
        document.getElementById('status-filter').value = 'all';

        applyFilters();

        function checkIsLate(item) {
            if (!item.submittedAt || !item.dueDate) return false;
            const due = new Date(item.dueDate.replace(/-/g, '/'));
            const sub = new Date(item.submittedAt.replace(/-/g, '/'));
            return sub > due;
        }

        function applyFilters() {
            const selectedSem = document.getElementById('semester-filter').value;
            const selectedStatus = document.getElementById('status-filter').value;

            const filtered = assignmentSubmissions.filter(item => {

                const matchSem =
                    selectedSem === 'all' ||
                    (item.semesterId !== null && String(item.semesterId) === String(selectedSem));

                const isLate = checkIsLate(item);
                let matchStatus = false;

                if (selectedStatus === 'all') {
                    matchStatus = true;
                } else if (selectedStatus === 'Late') {
                    matchStatus = isLate;
                } else {
                    matchStatus = (item.status === selectedStatus) && !isLate;
                }

                return matchSem && matchStatus;
            });

            updateStatisticsCards(filtered);
            renderSubjectGrouping(filtered);
        }

        function updateStatisticsCards(data) {
            let totalObtained = 0,
                totalMax = 0;
            let countAccepted = 0,
                countPending = 0,
                countOverdue = 0,
                countRejected = 0,
                countLate = 0;

            data.forEach(item => {
                const isLate = checkIsLate(item);
                if (isLate) countLate++;
                totalMax += item.maxPoints;

                if (item.status === 'Accepted') {
                    totalObtained += item.points;
                    if (!isLate) countAccepted++;
                } else if (item.status === 'Pending') {
                    if (!isLate) countPending++;
                } else if (item.status === 'Overdue') {
                    countOverdue++;
                } else if (item.status === 'Rejected') {
                    if (!isLate) countRejected++;
                }
            });

            document.getElementById('stat-total-obtained').innerText = totalObtained;
            document.getElementById('stat-total-max').innerText = totalMax;

            const pct = totalMax > 0 ? ((totalObtained / totalMax) * 100).toFixed(1) : 0;
            document.getElementById('stat-percentage').innerText = `${pct}% Grade Average`;

            document.getElementById('stat-accepted-count').innerHTML =
                `${countAccepted} <span class="small text-muted font-weight-normal">assignments</span>`;
            document.getElementById('stat-pending-count').innerHTML =
                `${countPending} <span class="small text-muted font-weight-normal">awaiting</span>`;

            const criticalCount = countOverdue + countRejected;
            document.getElementById('stat-overdue-count').innerHTML =
                `${criticalCount} <span class="small text-muted font-weight-normal">items</span>`;

            const subtitleEl = document.getElementById('stat-overdue-subtitle');
            if (criticalCount > 0) {
                subtitleEl.innerHTML = "{{ __('action_required') }}";
                subtitleEl.className = 'small text-danger font-weight-bold mt-1';
            } else {
                subtitleEl.innerHTML = "{{ __('zero_critical_backlog') }}";
                subtitleEl.className = 'small text-success font-weight-bold mt-1';
            }

            const total = data.length || 1;
            const pAcc = (countAccepted / total) * 100;
            const pLate = (countLate / total) * 100;
            const pPend = (countPending / total) * 100;
            const pOver = (countOverdue / total) * 100;
            const pRej = (countRejected / total) * 100;
            const done = countAccepted + countLate + countPending;

            document.getElementById('tracker-ratio-label').innerText =
                `${done} / ${data.length} Completed/Submitted (${((done / (data.length || 1)) * 100).toFixed(0)}%)`;

            document.getElementById('multi-progress-bar').innerHTML = `
            <div class="progress-bar bg-success"   style="width:${pAcc}%"></div>
            <div class="progress-bar bg-warning"   style="width:${pLate}%"></div>
            <div class="progress-bar bg-primary"   style="width:${pPend}%"></div>
            <div class="progress-bar bg-danger"    style="width:${pOver}%"></div>
            <div class="progress-bar bg-secondary" style="width:${pRej}%"></div>
        `;
        }

        function renderSubjectGrouping(data) {
            const container = document.getElementById('subject-report-container');
            container.innerHTML = '';

            if (data.length === 0) {
                container.innerHTML = `
                <div class="report-empty-state report-table-wrap">
                    <p class="text-muted font-weight-bold mb-0">
                       {{ __('no_assignments_match_filter') }}
                    </p>
                </div>`;
                return;
            }

            const grouped = {};
            data.forEach(item => {
                if (!grouped[item.subjectName]) grouped[item.subjectName] = [];
                grouped[item.subjectName].push(item);
            });

            for (const subject in grouped) {
                const assignments = grouped[subject];
                let subObtained = 0,
                    subMax = 0;
                assignments.forEach(a => {
                    subMax += a.maxPoints;
                    if (a.status === 'Accepted') subObtained += a.points;
                });
                const pct = subMax > 0 ? ((subObtained / subMax) * 100).toFixed(0) : 0;

                let rowsHTML = '';
                assignments.forEach(item => {
                    const isLate = checkIsLate(item);

                    let statusPill = '';
                    if (isLate) statusPill =
                        `<span class="badge badge-warning   font-weight-bold px-2 py-1">Late Submitted</span>`;
                    else if (item.status === 'Accepted') statusPill =
                        `<span class="badge badge-success   font-weight-bold px-2 py-1">Accepted</span>`;
                    else if (item.status === 'Pending') statusPill =
                        `<span class="badge badge-primary   font-weight-bold px-2 py-1">Pending</span>`;
                    else if (item.status === 'Overdue') statusPill =
                        `<span class="badge badge-danger    font-weight-bold px-2 py-1">Overdue</span>`;
                    else if (item.status === 'Rejected') statusPill =
                        `<span class="badge badge-secondary font-weight-bold px-2 py-1">Rejected</span>`;

                    let marksDisplay = '';
                    if (item.status === 'Accepted') {
                        marksDisplay =
                            `<span class="font-weight-bold text-dark" style="font-size:14px;">${esc(item.points)}</span> <span class="text-muted">/ ${esc(item.maxPoints)}</span>`;
                    } else if (item.status === 'Pending' && item.submittedAt) {
                        marksDisplay =
                            `<span class="font-italic text-warning small font-weight-bold">{{ __('awaiting_evaluate') }}</span>`;
                    } else if (item.status === 'Rejected') {
                        marksDisplay =
                            `<span class="font-weight-bold text-danger" style="font-size:14px;">${esc(item.points)}</span> <span class="text-muted">/ ${esc(item.maxPoints)}</span>`;
                    } else {
                        marksDisplay = `<span class="text-muted">- / ${esc(item.maxPoints)}</span>`;
                    }

                    let attachmentHTML = '';
                    if (item.attachedFile) {
                        const fname = item.attachedFile.split('/').pop();
                        // URL goes in a data attribute (HTML-escaped), never inside the inline JS string.
                        attachmentHTML = `
                        <div class="d-inline-block mt-2 small font-weight-bold text-secondary bg-white border py-1 px-2 rounded"
                             style="cursor:pointer;" data-open-url="${esc(httpUrl(item.attachedFile))}" onclick="window.open(this.getAttribute('data-open-url'),'_blank')">
                            {{ __('attachment') }}: <span style="color:var(--md-sys-color-primary);">${esc(fname)}</span>
                        </div>`;
                    } else if (item.submissionLink) {
                        attachmentHTML = `
                        <div class="d-inline-block mt-2 small font-weight-bold bg-light py-1 px-2 rounded"
                             style="cursor:pointer; color:var(--md-sys-color-primary);" data-open-url="${esc(httpUrl(item.submissionLink))}" onclick="window.open(this.getAttribute('data-open-url'),'_blank')">
                            {{ __('link') }}: <span class="text-secondary">${esc(item.submissionLink)}</span>
                        </div>`;
                    } else if (item.submissionText) {
                        attachmentHTML = `
                        <div class="d-inline-block mt-2 small text-secondary bg-light border py-1 px-2 rounded">
                            {{ __('text') }}:<span class="font-italic">${esc(item.submissionText.substring(0, 60))}…</span>
                        </div>`;
                    }

                    rowsHTML += `
                    <tr>
                        <td class="px-3 py-3">
                            <p class="font-weight-bold mb-1"
                               style="font-size:13px;cursor:pointer;color:var(--md-sys-color-primary);"
                               onclick="openReviewModal(${parseInt(item.id, 10)})">${esc(item.assignmentName)}</p>
                            <div class="small text-muted">
                               {{ __('semester') }}: <span class="font-weight-bold">${esc(item.semesterLabel)}</span>
                            </div>
                            ${attachmentHTML}
                        </td>
                        <td class="px-3 py-3 small text-muted">
                            <p class="mb-1">{{ __('due_date') }}: <span class="font-weight-bold text-dark">${esc(item.dueDate || '—')}</span></p>
                            ${item.submittedAt
                                ? `<p class="mb-0 text-success">{{ __('submitted') }}: <span class="font-weight-bold">${esc(item.submittedAt)}</span></p>`
                                : `<p class="mb-0 text-muted font-italic">{{ __('no_submission_date') }}</p>`}
                        </td>
                        <td class="px-3 py-3 align-middle">${statusPill}</td>
                        <td class="px-3 py-3 align-middle">${marksDisplay}</td>
                        <td class="px-3 py-3 align-middle text-center">
                            <button onclick="openReviewModal(${parseInt(item.id, 10)})"
                                    class="report-action-btn">{{ __('teacher_review') }}</button>
                        </td>
                    </tr>`;
                });

                container.innerHTML += `
                <div class="report-subject-group">
                    <div class="report-subject-group-header d-flex flex-column flex-sm-row align-items-sm-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <span class="d-inline-block rounded mr-2" style="width:6px;height:32px;background:var(--md-sys-color-primary);"></span>
                            <div>
                                <h5 class="report-subject-title mb-0">${esc(subject)}</h5>
                                <p class="small text-muted mb-0" style="font-size:11px;">
                                   {{ __('instructor') }}: ${esc(assignments[0].teacherName)}
                                </p>
                            </div>
                        </div>
                        <div class="report-subject-pill mt-2 mt-sm-0">
                            <span class="small font-weight-bold text-muted text-uppercase mr-2" style="font-size:10px;">{{ __('subject_gpa_ratio') }}</span>
                            <span class="small font-weight-bold mr-2" style="color:var(--md-sys-color-primary);">${subObtained} / ${subMax} pts</span>
                            <span class="badge badge-primary font-weight-bold px-2 py-1">${pct}%</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 text-left" style="font-size:12px;">
                            <thead class="thead-light">
                                <tr class="text-uppercase text-muted" style="font-size:10px;letter-spacing:0.5px;">
                                    <th class="px-3 py-2">{{ __('assignment_details') }}</th>
                                    <th class="px-3 py-2">{{ __('timeline') }}</th>
                                    <th class="px-3 py-2">{{ __('status') }}</th>
                                    <th class="px-3 py-2">{{ __('marks') }}</th>
                                    <th class="px-3 py-2 text-center">{{ __('action') }}</th>
                                </tr>
                            </thead>
                            <tbody>${rowsHTML}</tbody>
                        </table>
                    </div>
                </div>`;
            }
        }

        function openReviewModal(submissionId) {
            const item = assignmentSubmissions.find(s => s.id === parseInt(submissionId));
            if (!item) {
                console.warn('[Assignment] Submission not found for id:', submissionId);
                return;
            }

            document.getElementById('modal-student-name').innerText =
                @json($student->user?->full_name ?? $student->user?->first_name . ' ' . $student->user?->last_name);
            document.getElementById('modal-subject-label').innerText = item.subjectName;
            document.getElementById('modal-submitted-time').innerText = item.submittedAt ?
                `Submitted ${item.submittedAt}` : "{{ __('not_submitted') }}";
            document.getElementById('modal-eval-description').innerText = item.assignmentName;
            document.getElementById('modal-submission-text').innerText = item.submissionText || '—';

            // Hide the whole "Attached File" section when this submission has no file.
            const fileSection = document.getElementById('modal-file-section');
            const downloadBtn = document.getElementById('modal-download-btn');
            if (item.attachedFile) {
                fileSection.classList.remove('d-none');
                const fname = item.attachedFile.split('/').pop();
                document.getElementById('modal-filename-text').innerText = fname;
                downloadBtn.href = httpUrl(item.attachedFile);
                downloadBtn.setAttribute('download', fname);
                downloadBtn.classList.remove('d-none');
            } else {
                fileSection.classList.add('d-none');
            }

            const linkBox = document.getElementById('modal-link-box');
            if (item.submissionLink) {
                linkBox.innerHTML =
                    `<a href="${esc(httpUrl(item.submissionLink))}" target="_blank" style="color:var(--md-sys-color-primary);">${esc(item.submissionLink)}</a>`;
            } else {
                linkBox.innerText = 'No link provided';
            }

            document.getElementById('modal-points-obtained-val').innerText = item.points;
            document.getElementById('modal-points-max-val').innerText = item.maxPoints;
            const pct = item.maxPoints > 0 ? Math.round((item.points / item.maxPoints) * 100) : 0;
            document.getElementById('modal-points-percentage').innerText = `${pct}%`;

            const statusContainer = document.getElementById('modal-status-badge-container');
            const isLate = checkIsLate(item);
            if (isLate) statusContainer.innerHTML =
                `<span class="badge badge-warning   font-weight-bold px-3 py-2" style="font-size:11px;">Late Submitted</span>`;
            else if (item.status === 'Accepted') statusContainer.innerHTML =
                `<span class="badge badge-success   font-weight-bold px-3 py-2" style="font-size:11px;">Accepted</span>`;
            else if (item.status === 'Pending') statusContainer.innerHTML =
                `<span class="badge badge-primary   font-weight-bold px-3 py-2" style="font-size:11px;">Pending Review</span>`;
            else if (item.status === 'Overdue') statusContainer.innerHTML =
                `<span class="badge badge-danger    font-weight-bold px-3 py-2" style="font-size:11px;">Overdue</span>`;
            else if (item.status === 'Rejected') statusContainer.innerHTML =
                `<span class="badge badge-secondary font-weight-bold px-3 py-2" style="font-size:11px;">Rejected</span>`;

            document.getElementById('modal-feedback-text').innerText = item.feedback ||
                "{{ __('no_feedback_provided') }}";

            document.getElementById('review-submission-modal').classList.add('show-modal');
        }

        function closeReviewModal() {
            document.getElementById('review-submission-modal').classList.remove('show-modal');
        }

        document.getElementById('review-submission-modal').addEventListener('click', function(e) {
            if (e.target === this) closeReviewModal();
        });

        // Expose only what inline HTML attributes (onclick/onchange) need to call.
        // Plain property assignment on window — safe to repeat on every tab reload,
        // unlike `const`/`let` which throw if redeclared in the same scope.
        window.applyFilters = applyFilters;
        window.openReviewModal = openReviewModal;
        window.closeReviewModal = closeReviewModal;

    })();
</script>
