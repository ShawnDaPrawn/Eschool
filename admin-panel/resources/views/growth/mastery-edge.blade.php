<fieldset class="mastery-edge" data-mastery-edge data-mastery-edge-index="{{ $edgeIndex }}"><legend>{{ __('Sourced prerequisite') }}</legend>
    <x-page.field type="select" :restore-old-input="false" :id="'mastery-topic-'.$edgeIndex" :name="'links['.$edgeIndex.'][topic_id]'" :label="__('Topic')" :options="$graphNames" :value="$edge['topic_id'] ?? null" required />
    <x-page.field type="select" :restore-old-input="false" :id="'mastery-prerequisite-'.$edgeIndex" :name="'links['.$edgeIndex.'][prerequisite_id]'" :label="__('Prerequisite topic')" :options="$graphNames" :value="$edge['prerequisite_id'] ?? null" required />
    <x-page.field :restore-old-input="false" :id="'mastery-source-'.$edgeIndex" :name="'links['.$edgeIndex.'][source]'" :label="__('Source citation')" :value="$edge['source'] ?? ''" required minlength="3" maxlength="1000" />
    <button type="button" class="m3-button m3-button--outlined" data-mastery-remove>{{ __('Remove prerequisite') }}</button>
</fieldset>
