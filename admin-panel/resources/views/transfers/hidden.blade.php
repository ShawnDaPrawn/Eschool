@csrf
<input type="hidden" name="operation_id" value="{{ $operationId }}">
<input type="hidden" name="expected_account_id" value="{{ $account }}">
<input type="hidden" name="expected_school_id" value="{{ $school }}">
@if(isset($revision))<input type="hidden" name="revision" value="{{ $revision }}">@endif
