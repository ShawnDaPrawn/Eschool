<section class="m3-card m3-card--outlined panel-record-card" data-newsletter-editor data-section-label="{{ __('Section') }}" data-empty-label="{{ __('Add a heading or content before saving this section.') }}">
    <h3>{{ __('Newsletter sections') }}</h3>
    <p>{{ __('Use headings and paragraphs to build the edition. Sections are saved together in the message below. You can also edit that Markdown directly; rebuild the sections to reflect those changes.') }}</p>
    <div data-newsletter-sections></div>
    <div class="panel-actions">
        <button class="m3-button m3-button--outlined" type="button" data-newsletter-add>{{ __('Add section') }}</button>
        <button class="m3-button m3-button--text" type="button" data-newsletter-rebuild>{{ __('Rebuild sections from message') }}</button>
    </div>
    <p role="status" data-newsletter-status></p>
    <template data-newsletter-template>
        <fieldset data-newsletter-section>
            <legend data-section-legend>{{ __('Section') }}</legend>
            <label>{{ __('Section heading (optional)') }}<input class="m3-text-field__input" type="text" data-section-heading maxlength="191"></label>
            <label>{{ __('Section content') }}<textarea class="m3-text-field__input" rows="6" data-section-body></textarea></label>
            <div class="panel-actions"><button class="m3-button m3-button--text" type="button" data-section-up>{{ __('Move up') }}</button><button class="m3-button m3-button--text" type="button" data-section-down>{{ __('Move down') }}</button><button class="m3-button m3-button--text" type="button" data-section-remove data-remove-confirm="{{ __('Remove this newsletter section?') }}">{{ __('Remove section') }}</button></div>
        </fieldset>
    </template>
</section>
