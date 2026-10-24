<fieldset class="timetable-period" data-period-row><legend>{{ __('Teaching period or break') }}</legend>
    <div class="timetable-fields">
        <x-page.field :name="'periods['.$position.'][name]'" :id="'bell-period-'.$position.'-name'" :label="__('Period name')" :value="$period['name']" maxlength="100" />
        <x-page.field :name="'periods['.$position.'][starts_at]'" :id="'bell-period-'.$position.'-starts'" type="time" :label="__('Starts at')" :value="substr($period['starts_at'], 0, 5)" />
        <x-page.field :name="'periods['.$position.'][ends_at]'" :id="'bell-period-'.$position.'-ends'" type="time" :label="__('Ends at')" :value="substr($period['ends_at'], 0, 5)" />
    </div>
    <input type="hidden" name="periods[{{ $position }}][is_break]" value="0"><label><input type="checkbox" name="periods[{{ $position }}][is_break]" value="1" @checked($period['is_break'])> {{ __('Break') }}</label>
</fieldset>
