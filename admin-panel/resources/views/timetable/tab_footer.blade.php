{{-- Footer action bar — add another period (data-repeater-create / .addmore, shown by JS
     once a class-section is picked) + submit. Closes the <form> and .repeater from tab_title. --}}
<div class="tt-footer">
    <button data-repeater-create type="button" class="addmore tt-add d-none">
        <x-icon name="plus" :size="16" /> {{ __('add') . ' ' . __('Period') }}
    </button>
    <button class="btn btn-theme" id="create-btn" type="submit">{{ __('submit') }}</button>
</div>
</form>
</div>
