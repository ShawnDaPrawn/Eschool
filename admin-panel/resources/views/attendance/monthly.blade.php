@extends('layouts.master')

@section('title')
    {{ __('attendance') }}
@endsection

@section('css')
    <link rel="stylesheet"
        href="{{ asset('/assets/css/attendance.css') }}?v={{ filemtime(public_path('assets/css/attendance.css')) }}">
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            
            <h3 class="page-title">{{ __('Manage Attendance') }}</h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Attendance') }}</h4>
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-2">
                                <label>{{ __('Class Section') }}</label>
                                <select id="filter_class_section_id" class="form-control">
                                    @foreach ($class_sections as $cs)
                                        <option value="{{ $cs->id }}" {{ $loop->first ? 'selected' : '' }}>
                                            {{ $cs->class->name }} {{ $cs->section->name }}
                                            {{ $cs->class->medium->name ?? '' }}
                                            {{ $cs->class->streams->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-sm-12 col-md-2">
                                <label>{{ __('Month') }}</label>
                                <select id="filter_month" class="form-control">
                                    @php
                                        $sessionStart = \Carbon\Carbon::parse($currentSessionYear->start_date);
                                        $sessionEnd = \Carbon\Carbon::parse($currentSessionYear->end_date);
                                        $current = $sessionStart->copy()->startOfMonth();
                                    @endphp
                                    @while ($current->lte($sessionEnd))
                                        <option value="{{ $current->format('Y-m') }}"
                                            {{ $current->format('Y-m') === date('Y-m') ? 'selected' : '' }}>
                                            {{ $current->format('F Y') }}
                                        </option>
                                        @php $current->addMonth() @endphp
                                    @endwhile
                                </select>
                            </div>
                        </div>

                        {{-- Legend --}}
                        <div class="attendance-legend">
    <span class="legend-title">{{ __('Attendance Summary') }}:</span>
    <span class="legend-item">
        <span class="attendance-dot dot-present"></span> {{ __('Present') }}
    </span>
    <span class="legend-item">
        <span class="attendance-dot dot-absent"></span> {{ __('Absent') }}
    </span>
    <span class="legend-item">
        <span class="attendance-dot dot-half"></span> {{ __('Half Day') }}
    </span>
    <span class="legend-item">
        <span class="attendance-dot dot-holiday"></span> {{ __('Holiday') }}
    </span>
    <span class="legend-item">
        <span class="attendance-dot dot-leave"></span> {{ __('Leave') }}
    </span>
    <span class="legend-item">
        <span class="attendance-dot dot-none"></span> {{ __('No Record') }}
    </span>
</div>

                        {{-- Table --}}
                        <div class="row">
                            <div class="col-12">
                                <div id="attendance_table_wrapper">
                                    <p class="text-muted text-center py-4">
                                        {{ __('Loading...') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        var Lang = {
            studentDetails: "{{ __('student_details') }}",
            loading: "{{ __('loading') }}",
            selectFilter: "{{ __('select_class_section_and_month') }}",
            noStudents: "{{ __('no_students_found') }}",
            failedToLoad: "{{ __('failed_load_attndance') }}",
        };

        var dotClassMap = {
    'P': 'dot-present',
    'A': 'dot-absent',
    'HD': 'dot-half',
    'H': 'dot-holiday',
    'L': 'dot-leave',
};

        var defaultAvatar = "{{ asset('assets/avatar.png') }}";

        function loadAttendance() {
            const classSectionId = $('#filter_class_section_id').val();
            const month = $('#filter_month').val();

            if (!classSectionId || !month) {
                $('#attendance_table_wrapper').html(
                    '<p class="text-muted text-center py-4">' + Lang.selectFilter + '</p>');
                return;
            }

            const [year, mon] = month.split('-');
            const daysInMonth = new Date(year, parseInt(mon), 0).getDate();

            let headRow = `<th>${Lang.studentDetails}</th>`;
            for (let d = 1; d <= daysInMonth; d++) {
                headRow += `<th>${d}</th>`;
            }

            $('#attendance_table_wrapper').html(`
                <div class="monthly-table-wrap">
                    <table class="monthly-table">
                        <thead><tr>${headRow}</tr></thead>
                        <tbody id="attendance_tbody">
                            <tr>
                                <td colspan="${daysInMonth + 1}" style="text-align:center;padding:2rem;color:#8c8c8c;">
                                    <span class="spinner-border spinner-border-sm"></span> ${Lang.loading}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `);

            $.ajax({
                url: '{{ url('attendance/monthly-ajax') }}',
                type: 'GET',
                data: {
                     class_section_id: classSectionId,
                        month: month,
                        session_year_id: $('#filter_session_year_global').val()
                    },
                success: function(response) {
                    if (!response.students || response.students.length === 0) {
                        $('#attendance_tbody').html(`
                            <tr>
                                <td colspan="${daysInMonth + 1}" style="text-align:center;padding:2rem;color:#8c8c8c;">
                                    ${Lang.noStudents}
                                </td>
                            </tr>
                        `);
                        return;
                    }

                    let rows = '';

                    response.students.forEach((student) => {
                        const name = (student.full_name || '').trim();
                        const avatar =
                            `<img src="${student.image || defaultAvatar}" class="attendance-avatar-img" alt="" onerror="onErrorAvatar(event)">`;

                        let dayCells = '';
                        for (let d = 1; d <= daysInMonth; d++) {
                            const status = (response.attendance[student.id] && response.attendance[
                                    student.id][d]) ?
                                response.attendance[student.id][d] : null;
                            const dotClass = status ? (dotClassMap[status] || 'dot-none') : 'dot-none';
                            dayCells += `<td><span class="attendance-dot ${dotClass}"></span></td>`;
                        }

                        rows += `
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        ${avatar}
                                        <span class="att-name">${name}</span>
                                    </div>
                                </td>
                                ${dayCells}
                            </tr>`;
                    });

                    $('#attendance_tbody').html(rows);
                },
                error: function() {
                    $('#attendance_tbody').html(`
                        <tr>
                            <td colspan="${daysInMonth + 1}" style="text-align:center;padding:2rem;color:#dc3545;">
                                ${Lang.failedToLoad}
                            </td>
                        </tr>
                    `);
                }
            });
        }
        $('#filter_session_year_global').on('change', function () {
    loadMonthsBySessionYear();
});


function loadMonthsBySessionYear() {

    let sessionYearId = $('#filter_session_year_global').val();

    $.ajax({
        url: "{{ url('get-session-year-months') }}",
        type: "GET",
        data: {
            session_year_id: sessionYearId
        },
        success: function (response) {

            let html = '';

            response.months.forEach(function (month) {
                html += `<option value="${month.value}">${month.text}</option>`;
            });

            $('#filter_month').html(html);

            loadAttendance();
        }
    });
}

    $(document).ready(function () {

    loadMonthsBySessionYear();

    $('#filter_class_section_id, #filter_month').on('change', function () {
        loadAttendance();
    });

    $('#filter_session_year_global').on('change', function () {
        loadMonthsBySessionYear();
    });

});
    </script>
@endsection
