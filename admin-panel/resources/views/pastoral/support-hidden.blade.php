@csrf
<input type="hidden" name="expected_school_id" value="{{ app(\App\Services\Platform\Tenancy\CurrentSchool::class)->id() }}">
<input type="hidden" name="expected_account_id" value="{{ auth()->id() }}">
<input type="hidden" name="audience" value="{{ $audience }}">
<input type="hidden" name="operation_id" value="{{ $draftKey === $formKey ? $operationId : (string) \Illuminate\Support\Str::uuid() }}">
@if(isset($revision))<input type="hidden" name="revision" value="{{ $revision }}">@endif
@if($conflict && $draftKey === $formKey)
<input type="hidden" name="review_required" value="1">
<label for="support-confirm-{{ str_replace(':','-', $formKey) }}"><input id="support-confirm-{{ str_replace(':','-', $formKey) }}" name="confirm_current" type="checkbox" value="1" required> {{ __('I compared my draft with the current saved record.') }}</label>
@endif
