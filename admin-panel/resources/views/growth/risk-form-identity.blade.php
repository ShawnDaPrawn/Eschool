@csrf
<input type="hidden" name="operation_id" value="{{ $operationId }}">
<input type="hidden" name="expected_account_id" value="{{ $actorId }}">
<input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
<input type="hidden" name="session_year_id" value="{{ $year }}">
<input type="hidden" name="mode" value="{{ $mode }}">
@if($conflict)<input type="hidden" name="review_required" value="1"><label class="note-confirm"><input type="checkbox" name="confirm_current" value="1" required> {{ __('I compared this draft with the current saved intervention.') }}</label>@endif
