<tr data-fee-rule><td>
<x-m3.select :name="'rule_rows['.$index.'][field]'" :label="__('Rule field')" :options="$ruleOptions" :value="$condition?($condition['field']==='phase'?'phase':'profile:'.$condition['definition_id']):''" />
</td><td>
<x-m3.text-field type="textarea" :id="'fee-rule-values-'.$index" :name="'rule_rows['.$index.'][values]'" :label="__('Matching values, one per line')" :hint="__('Use Foundation, Intermediate, Senior or FET for phase; Yes or No for boolean fields.')" :value="$condition?implode(PHP_EOL,array_map(fn($v)=>is_bool($v)?($v?__('Yes'):__('No')):$v,$condition['values'])):''" rows="2" maxlength="16000" />
</td><td><x-m3.button type="button" variant="text" data-remove-rule>{{ __('Remove rule') }}</x-m3.button></td></tr>
