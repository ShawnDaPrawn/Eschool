{{-- Period row fields — cloned by the jquery.repeater plugin (data-repeater-item).
     Plain field names (subject_id, teacher_id, …) are auto-indexed by the plugin into
     <day>_group[i][field]. JS hooks (set_subject_id, set_teacher_id, timetable_start_time,
     timetable_end_time, timepicker-popup) and the delete button (data-repeater-delete)
     must be preserved — see public/assets/js/custom/timetable.js. --}}
<div class="tt-cell tt-cell--wide" data-label="{{ __('subjects') }}">
    <select required name="subject_id" class="form-control set_subject_id" style="width:100%;" tabindex="-1"
        aria-hidden="true"></select>
</div>
<div class="tt-cell tt-cell--wide" data-label="{{ __('teacher') }}">
    <select required name="teacher_id" class="form-control set_teacher_id" style="width:100%;" tabindex="-1"
        aria-hidden="true"></select>
</div>
<div class="tt-cell" data-label="{{ __('start_time') }}">
    <input required type="text" name="start_time" class="timetable_start_time form-control timepicker-popup"
        placeholder="{{ __('start_time') }}">
</div>
<div class="tt-cell" data-label="{{ __('end_time') }}">
    <input required type="text" name="end_time" class="timetable_end_time form-control timepicker-popup"
        placeholder="{{ __('end_time') }}">
</div>
<div class="tt-cell" data-label="{{ __('live_class_url') }}">
    <input type="text" name="live_class_url" class="form-control" placeholder="{{ __('Link') }}">
</div>
<div class="tt-cell" data-label="{{ __('Name') }}">
    <input type="text" name="link_name" class="form-control" placeholder="{{ __('Name') }}">
</div>
<button data-repeater-delete type="button" class="row_remove tt-row__remove" data-label="{{ __('Delete') }}"
    title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">
    <x-icon name="trash" :size="16" />
</button>
