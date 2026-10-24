{{-- Opens the .repeater (jquery.repeater) + the per-day <form>; closed in tab_footer.
     The grid header mirrors the .tt-row column layout (see timetable.css) and is hidden
     on ≤991px where each field carries its own data-label. --}}
<div class="repeater">
    <div class="tt-grid-head">
        <span class="tt-grid-head__label">
            <x-icon name="subject" :size="15" /> {{ __('subjects') }} <span class="req">*</span>
        </span>
        <span class="tt-grid-head__label">
            <x-icon name="teacher" :size="15" /> {{ __('teacher') }} <span class="req">*</span>
        </span>
        <span class="tt-grid-head__label">
            <x-icon name="clock" :size="15" /> {{ __('start_time') }} <span class="req">*</span>
        </span>
        <span class="tt-grid-head__label">
            <x-icon name="clock" :size="15" /> {{ __('end_time') }} <span class="req">*</span>
        </span>
        <span class="tt-grid-head__label">
            <x-icon name="link" :size="15" /> {{ __('live_class_url') }}
        </span>
        <span class="tt-grid-head__label">
            {{ __('Name') }}
        </span>
        <span></span>
    </div>

    <form class="pt-0" action="{{ url('timetable') }}" id="formdata" method="POST" novalidate="novalidate">
        @csrf
        <input required type="hidden" name="day" id="day" class="day">
        <input required type="hidden" name="session_year_id" id="session_year_id">
        <input required type="hidden" name="class_section_id" id="class_section_id">
        <input type="hidden" name="term_id" id="term_id">
