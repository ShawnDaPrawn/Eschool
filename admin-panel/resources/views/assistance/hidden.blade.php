@csrf
<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
<input type="hidden" name="operation_id" value="{{ $draftOperation === $operation ? $operationId : (string) \Illuminate\Support\Str::uuid() }}">
@if(isset($revision))<input type="hidden" name="expected_revision" value="{{ $revision }}">@endif
