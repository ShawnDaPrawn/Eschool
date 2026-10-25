@csrf
<input type="hidden" name="expected_account_id" value="{{ $configuration['account_id'] }}">
<input type="hidden" name="expected_school_id" value="{{ $configuration['school_id'] }}">
<input type="hidden" name="operation_id" value="{{ $intent ?? (string) \Illuminate\Support\Str::uuid() }}">
