@csrf
<input type="hidden" name="operation_id" value="{{ (string) Illuminate\Support\Str::uuid() }}">
<input type="hidden" name="expected_account_id" value="{{ $account }}">
<input type="hidden" name="expected_school_id" value="{{ $school }}">
@if(isset($revision))<input type="hidden" name="revision" value="{{ $revision }}">@endif
@if(isset($learner) && $learner !== null)<input type="hidden" name="learner_id" value="{{ $learner }}">@endif

@if($assembly ?? false)<input type="hidden" name="assembly" value="1">@endif
