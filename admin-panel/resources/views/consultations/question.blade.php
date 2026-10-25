<fieldset class="m3-card consultation-question" data-question data-index="{{ $qIndex }}">
<legend>{{ __('Question') }}</legend>
<label for="question-kind-{{ $qIndex }}">{{ __('Question type') }}<select id="question-kind-{{ $qIndex }}" name="questions[{{ $qIndex }}][kind]" data-question-kind>@foreach(['single'=>'Single choice','multiple'=>'Multiple choice','text'=>'Text answer'] as $key=>$label)<option value="{{ $key }}" @selected(($question['kind']??'single')===$key)>{{ __($label) }}</option>@endforeach</select></label>
<label for="question-label-{{ $qIndex }}">{{ __('Question wording') }}<textarea id="question-label-{{ $qIndex }}" name="questions[{{ $qIndex }}][label]" maxlength="1000" required>{{ $question['label'] ?? '' }}</textarea></label>
<input type="hidden" name="questions[{{ $qIndex }}][required]" value="0"><label class="consultation-check"><input type="checkbox" name="questions[{{ $qIndex }}][required]" value="1" @checked($question['required']??true)>{{ __('Answer required') }}</label>
<label for="question-max-{{ $qIndex }}" data-max-choices>{{ __('Maximum choices') }}<input id="question-max-{{ $qIndex }}" type="number" name="questions[{{ $qIndex }}][max_choices]" min="1" max="50" value="{{ $question['max_choices']??1 }}"></label>
<div data-options>@foreach($question['options']??[] as $oIndex=>$option)@include('consultations.option')@endforeach</div>
<button type="button" class="m3-button m3-button--outlined" data-add-option>{{ __('Add choice') }}</button>
<button type="button" class="m3-button m3-button--text" data-remove-question>{{ __('Remove question') }}</button>
</fieldset>
