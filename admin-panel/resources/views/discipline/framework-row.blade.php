<tr data-disc-row="{{ $kind }}">
<td><label for="disc-{{ $kind }}-{{ $index }}-code">{{ __('Code') }}</label><input id="disc-{{ $kind }}-{{ $index }}-code" name="{{ $kind }}[{{ $index }}][code]" value="{{ $row['code']??'' }}" maxlength="40" pattern="[a-z][a-z0-9_]{0,39}"></td>
<td><label for="disc-{{ $kind }}-{{ $index }}-name">{{ __('Name') }}</label><input id="disc-{{ $kind }}-{{ $index }}-name" name="{{ $kind }}[{{ $index }}][name]" value="{{ $row['name']??'' }}" maxlength="150"></td>
@if($kind==='levels')
<td><label for="disc-levels-{{ $index }}-threshold">{{ __('Occurrences') }}</label><input id="disc-levels-{{ $index }}-threshold" type="number" min="1" max="1000" name="levels[{{ $index }}][threshold]" value="{{ $row['threshold']??'' }}"></td>
<td><label for="disc-levels-{{ $index }}-kind">{{ __('Stage') }}</label><select id="disc-levels-{{ $index }}-kind" name="levels[{{ $index }}][kind]"><option value="">{{ __('Choose a stage') }}</option>@foreach(['sanction'=>'School sanction','hearing'=>'Principal hearing'] as $value=>$label)<option value="{{ $value }}" @selected(($row['kind']??'')===$value)>{{ __($label) }}</option>@endforeach</select></td>
<td><label for="disc-levels-{{ $index }}-sanctions">{{ __('Approved sanctions') }}</label><select id="disc-levels-{{ $index }}-sanctions" name="levels[{{ $index }}][sanctions][]" multiple data-disc-sanction-choices>@foreach($definition['sanctions'] as $sanction)<option value="{{ $sanction['code'] }}" @selected(in_array($sanction['code'],$row['sanctions']??[],true))>{{ $sanction['name'] }}</option>@endforeach</select></td>
@elseif($kind==='sanctions')
<td><label for="disc-sanctions-{{ $index }}-kind">{{ __('Sanction type') }}</label><select id="disc-sanctions-{{ $index }}-kind" name="sanctions[{{ $index }}][kind]"><option value="">{{ __('Choose a type') }}</option>@foreach(['notice'=>'School notice','detention'=>'Supervised detention','restorative'=>'Restorative action'] as $value=>$label)<option value="{{ $value }}" @selected(($row['kind']??'')===$value)>{{ __($label) }}</option>@endforeach</select></td>
@else
<td><label for="disc-infractions-{{ $index }}-base">{{ __('Starting level') }}</label><select id="disc-infractions-{{ $index }}-base" name="infractions[{{ $index }}][base_level]" data-disc-level-choices>@foreach($definition['levels'] as $level)<option value="{{ $level['code'] }}" @selected(($row['base_level']??'')===$level['code'])>{{ $level['name'] }}</option>@endforeach</select></td>
@endif
<td><button type="button" class="btn btn-outline-secondary" data-disc-remove>{{ __('Remove row') }}</button></td>
</tr>
