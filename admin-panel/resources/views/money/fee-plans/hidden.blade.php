@csrf
<input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
<input type="hidden" name="revision" value="{{ $revision }}">
