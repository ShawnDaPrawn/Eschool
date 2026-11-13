<x-m3.text-field :id="$prefix.'-name'" name="name" :label="__('Name')" :value="$values['name'] ?? ''" maxlength="120" required />
<x-m3.select :id="$prefix.'-area'" name="concern_area" :label="__('Concern area')" required>@foreach ($areaNames as $key => $label)<option value="{{ $key }}" @selected(old('concern_area', $values['concern_area'] ?? '') === $key)>{{ $label }}</option>@endforeach</x-m3.select>
<label for="{{ $prefix }}-steps">{{ __('Steps, one per line (at most :max)', ['max' => $maxSteps]) }}</label>
<textarea id="{{ $prefix }}-steps" name="steps_text" rows="5" maxlength="2500" required>{{ old('steps_text', implode("\n", $values['steps'] ?? [])) }}</textarea>
@include('people.partials.structured-list', ['sourceField' => 'steps_text', 'maximum' => $maxSteps, 'rowMaxlength' => 300, 'listTitle' => __('Action steps'), 'rowLabel' => __('Step'), 'addLabel' => __('Add step')])
<x-m3.text-field :id="$prefix.'-weeks'" name="suggested_weeks" type="number" min="1" max="26" :label="__('Suggested length in weeks')" :value="$values['suggested_weeks'] ?? 4" required />
<x-m3.text-field :id="$prefix.'-measure'" name="progress_measure" :label="__('Measure of progress')" :value="$values['progress_measure'] ?? ''" maxlength="500" required />
