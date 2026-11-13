<x-page.field :id="$prefix.'-monthly-leave'" name="total_leave" type="number" :label="__('Leave allowance per month')" min="0" step="1" required />
<x-page.field :id="$prefix.'-leave-year'" name="session_year_id" type="select" :label="__('Configuration year')" :options="$session_year->pluck('name', 'id')->all()" :value="app(\App\Services\Panel\SessionYearContext::class)->selectedId()" :help="__('This configures the selected year. It does not activate a school year.')" required />
<fieldset><legend>{{ __('Non-working days') }}</legend>
    <div class="m3-row m3-gap-4">
        @foreach(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
            <label class="m3-checkbox"><input class="m3-checkbox__input" type="checkbox" name="holiday_days[]" value="{{ $day }}"><span class="m3-checkbox__control"></span><span>{{ __($day) }}</span></label>
        @endforeach
    </div>
</fieldset>
