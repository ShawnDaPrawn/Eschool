@php
    $adminRequest = $adminRequest ?? false;
    $returnUrl = route($adminRequest ? 'leave-request.index' : 'leave.index');
    $dateMin = $currentSessionYear?->start_date;
    if (!$adminRequest && !auth()->user()->hasRole('Super Admin')) $dateMin = max((string) $dateMin, now()->toDateString());
@endphp
@if(!$leaveMaster)
    <x-page.empty-state :title="__('Leave configuration is required')" :message="__('Configure the monthly leave allowance and non-working days for the current school year before requesting leave.')" />
    @can('leave-setting-create')<a class="m3-button m3-button--outlined" href="{{ route('leave-master.index') }}">{{ __('Open leave configuration') }}</a>@endcan
@else
    <x-page.form-card id="leave-create-form" :action="route($adminRequest ? 'leave.store.admin' : 'leave.store')"
        :title="$adminRequest ? __('Record staff leave') : __('Request leave')" :draft="false"
        :submit-label="$adminRequest ? __('Record approved leave') : __('Submit leave request')" :redirect="$returnUrl" :cancel-href="$returnUrl">
        <div data-leave-dates data-holidays="{{ $holiday_days ?? '' }}" data-public-holidays="{{ $public_holiday ?? '' }}">
            @if($adminRequest)
                <p class="m3-banner m3-banner--info">{{ __('Office-created staff leave is recorded as approved. Review the staff member and each day before submitting.') }}</p>
                <x-page.field name="staff_id" type="select" :label="__('Staff member')" :options="$modalUsers->mapWithKeys(fn ($person) => [$person->id => $person->full_name])->all()" required />
            @else
                <input type="hidden" name="leave_master_id" value="{{ $leaveMaster->id }}">
            @endif
            <div class="m3-grid">
                <div class="m3-col-12 m3-col-expanded-6"><x-page.field id="leave-from" name="from_date" type="date" :label="__('From date')" :min="$dateMin" :max="$currentSessionYear?->end_date" required /></div>
                <div class="m3-col-12 m3-col-expanded-6"><x-page.field id="leave-to" name="to_date" type="date" :label="__('To date')" :min="$dateMin" :max="$currentSessionYear?->end_date" required /></div>
            </div>
            <x-page.field name="reason" type="textarea" :label="__('Reason')" required />
            <x-page.field name="files[]" type="file" :label="__('Supporting documents')" :help="__('You can select more than one file. Files must be chosen again after leaving this page.')" multiple />
            <fieldset><legend>{{ __('Leave portion for each working day') }}</legend>
                <p>{{ __('Choose full day, first half or second half for every eligible day. Non-working days and public holidays are excluded.') }}</p>
                <div data-leave-portions aria-live="polite" data-empty="{{ __('Choose the date range to see eligible days.') }}" data-no-days="{{ __('There are no working days in this date range.') }}" data-full="{{ __('Full day') }}" data-first="{{ __('First half') }}" data-second="{{ __('Second half') }}"></div>
            </fieldset>
        </div>
    </x-page.form-card>
@endif
