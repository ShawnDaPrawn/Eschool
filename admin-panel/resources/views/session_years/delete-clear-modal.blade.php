{{-- Cleanup is a dedicated review surface. The confirmation uses the shared native M3 dialog. --}}
<section class="page-section" data-school-year-cleanup data-year-name="{{ $selectedYear->name }}">
<h2>{{ __('Review school-year cleanup') }} · {{ $selectedYear->name }}</h2>
<p class="m3-banner m3-banner--warning">{{ __('Removal is destructive. Cleanup does not activate a year. Review the selected categories before confirming.') }}</p>
<div data-year-cleanup-result role="status" tabindex="-1" hidden></div>
<div data-year-cleanup-review>
<h3>{{ __('Existing related records') }}</h3>
<ul>@foreach($related as $relation=>$count)<li>{{ __(Illuminate\Support\Str::headline($relation)) }}: {{ $count }}</li>@endforeach</ul>
<p>{{ __('These counts describe the existing cleanup categories. Newer domain and financial history follow their own retention rules.') }}</p>
@if($deletionBlock)<p class="m3-banner m3-banner--warning">{{ $deletionBlock }}</p>@endif
@if((int)$selectedYear->id !== (int)$current?->id)
<label class="m3-text-field m3-text-field--select"><select class="m3-text-field__input" data-year-cleanup-choice><option value="categories">{{ __('Review selected data categories') }}</option><option value="year">{{ __('Review deletion of this school year') }}</option></select><span class="m3-text-field__label">{{ __('Cleanup operation') }}</span></label>
<div data-year-cleanup-categories>
<x-page.form-card id="school-year-clear" :action="url('session-years/'.$selectedYear->id.'/clear-specific')" :title="__('Select school-year data to clear')" :submit-label="__('Review selected clearing')" :draft="false" :write="false" :cancel-href="route('session-years.index')">
@foreach(['class_subject'=>'Class subjects','class_teachers'=>'Class and subject teachers','notifications'=>'Notifications','timetable'=>'Timetable','attendance'=>'Learner attendance','assignments'=>'Assignments','exams'=>'Online and offline exams','grades'=>'Exam grades','announcements'=>'Announcements','holidays'=>'Holidays','events'=>'Events','leaves'=>'Student and staff leave','allowed_leave_days'=>'Monthly leave configuration'] as $key=>$label)
<label class="m3-checkbox"><input type="checkbox" name="clear_data[]" value="{{ $key }}"><span class="m3-checkbox__control"></span>{{ __($label) }}</label>
@endforeach
<p>{{ __('Only selected categories are sent to the existing cleanup service. This does not clear facilitator absence or new academic register history.') }}</p>
</x-page.form-card>
</div>
<div data-year-cleanup-whole hidden>
@if(!$deletionBlock)
<x-page.form-card id="school-year-delete" :action="route('session-years.destroy',$selectedYear->id)" method="DELETE" :title="__('Review deletion of this school year')" :submit-label="__('Review school-year deletion')" :draft="false" :write="false" :cancel-href="route('session-years.index')">
<p>{{ __('The selected school year and its original related records above will be removed. Current financial configuration or history prevents deletion.') }}</p>
</x-page.form-card>
@else<p>{{ __('Keep this year available. School-year deletion is unavailable.') }}</p>@endif
</div>
@endif
</div>
</section>
