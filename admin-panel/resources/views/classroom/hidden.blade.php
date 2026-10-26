@csrf
<input type="hidden" name="expected_account_id" value="{{ $data['reader_id'] }}">
<input type="hidden" name="expected_school_id" value="{{ $data['school_id'] }}">
@if($operationRequired ?? true)<input type="hidden" name="operation_id" value="{{ \Illuminate\Support\Str::uuid() }}">@endif
