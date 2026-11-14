<tr data-installment><td>
<x-m3.select :name="'option_rows['.$optionIndex.'][installments]['.$index.'][month]'" :label="__('Installment month')" :options="array_combine(range(1,12),range(1,12))" :value="$part['month']??null" :placeholder="__('Choose month')" />
</td><td>
<x-m3.text-field type="number" :name="'option_rows['.$optionIndex.'][installments]['.$index.'][day]'" :label="__('Installment day')" min="1" max="31" :value="$part['day']??''" />
</td><td>
@php($weight=$part['weight_bp']??null)
<x-m3.text-field :name="'option_rows['.$optionIndex.'][installments]['.$index.'][percentage]'" :label="__('Installment percentage')" inputmode="decimal" pattern="(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)" :value="$weight===null?'':intdiv($weight,100).'.'.str_pad((string)($weight%100),2,'0',STR_PAD_LEFT)" />
</td><td><x-m3.button type="button" variant="text" data-remove-installment>{{ __('Remove installment') }}</x-m3.button></td></tr>
