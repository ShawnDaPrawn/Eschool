@php
    $identityKey = $operation.':'.$target;
    $submitted = $draftKey === $identityKey;
    $identityUuid = $submitted ? $operationId : (string) \Illuminate\Support\Str::uuid();
@endphp
@csrf
<input type="hidden" name="operation_id" value="{{ $identityUuid }}">
<input type="hidden" name="expected_account_id" value="{{ $actorId }}">
<input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
@if($submitted && $conflict)
<input type="hidden" name="review_required" value="1">
<label for="wb-confirm-{{ $operation }}-{{ $target }}"><input id="wb-confirm-{{ $operation }}-{{ $target }}" type="checkbox" name="confirm_current" value="1" required> {{ __('I compared my draft with the current record and want to save it') }}</label>
@endif
