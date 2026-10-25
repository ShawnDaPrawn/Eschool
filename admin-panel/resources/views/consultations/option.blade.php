<div class="consultation-option" data-option>
<label for="option-{{ $qIndex }}-{{ $oIndex }}">{{ __('Choice or candidate name') }}<input id="option-{{ $qIndex }}-{{ $oIndex }}" name="questions[{{ $qIndex }}][options][{{ $oIndex }}][label]" maxlength="191" required value="{{ $option['label'] ?? '' }}"></label>
<label data-candidate-statement for="statement-{{ $qIndex }}-{{ $oIndex }}">{{ __('Candidate statement') }}<textarea id="statement-{{ $qIndex }}-{{ $oIndex }}" name="questions[{{ $qIndex }}][options][{{ $oIndex }}][statement]" maxlength="2000">{{ $option['statement'] ?? '' }}</textarea></label>
<button type="button" class="m3-button m3-button--text" data-remove-option>{{ __('Remove choice') }}</button>
</div>
