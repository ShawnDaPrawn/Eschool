@csrf
<input type="hidden" name="session_year_id" value="{{ $year->id }}">
<input type="hidden" name="from" value="{{ $from }}"><input type="hidden" name="to" value="{{ $to }}">
<input type="hidden" name="operation_id" value="{{ $formOperation ?? (string) \Illuminate\Support\Str::uuid() }}">
@if ($messages)
<input type="hidden" name="review_required" value="1">
<label><input type="checkbox" name="confirm_current" value="1" required> {{ __('I compared my changes with the current cover record.') }}</label>
@endif
