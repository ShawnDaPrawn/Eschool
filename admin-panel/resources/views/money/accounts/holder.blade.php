<div class="m3-card m3-card--outlined fee-plan-card" data-billing-holder><div class="m3-card__content">
    <x-m3.select :name="'holders['.$index.'][user_id]'" :label="__('Account holder :number',['number'=>$number])" :options="$parents" :value="$holder?->user_id" :id="'billing-holder-'.$index" :placeholder="__('Choose a guardian')" :required="$index===0" />
    @php $bp=$holder?->share_bp; $percentage=$bp===null?($index===0?'100.00':''):intdiv($bp,100).'.'.str_pad((string)($bp%100),2,'0',STR_PAD_LEFT); @endphp
    <x-m3.text-field :name="'holders['.$index.'][share_percent]'" :label="__('Holder share percentage :number',['number'=>$number])" :value="$percentage" :id="'billing-share-'.$index" inputmode="decimal" pattern="(?:100(?:\.0{1,2})?|\d{1,2}(?:\.\d{1,2})?)" :required="$index===0" />
    <x-m3.button type="button" variant="text" data-remove-holder>{{ __('Remove holder') }}</x-m3.button>
</div></div>
