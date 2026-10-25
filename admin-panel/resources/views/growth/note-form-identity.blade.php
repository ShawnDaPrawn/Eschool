@php
    $formSubmitted = !request()->isMethodSafe() && request()->route()->getName() === 'learner-notes.'.$operation;
    $formUuid = $formSubmitted ? $operationId : (string) \Illuminate\Support\Str::uuid();
@endphp
@csrf
<input type="hidden" name="operation_id" value="{{ $formUuid }}">
<input type="hidden" name="expected_account_id" value="{{ $actorId }}">
<input type="hidden" name="expected_school_id" value="{{ $schoolId }}">
@if($formSubmitted && $conflict)
    <input type="hidden" name="review_required" value="1">
    <label class="note-confirm" for="confirm-{{ $operation }}-{{ $target }}"><input id="confirm-{{ $operation }}-{{ $target }}" type="checkbox" name="confirm_current" value="1" required> {{ __('I compared my draft with the current record and want to save it') }}</label>
@endif
