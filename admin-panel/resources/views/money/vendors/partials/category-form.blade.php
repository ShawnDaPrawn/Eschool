    <form method="post" action="{{ route('vendor-catalogues.save',['kind'=>'category']) }}" aria-label="{{ __('Save vendor category') }}" data-year-safe>
        @include('money.fee-plans.hidden',['revision'=>$category?->revision??0])
        @if($category)<input type="hidden" name="id" value="{{ $category->id }}">@endif
        <fieldset @disabled(!$canWrite)><legend>{{ __('Category details') }}</legend>
            <x-page.field id="category-key" :restore-old-input="false" name="key" :label="__('Permanent category key')" :value="$category?->key" :readonly="(bool)$category" required maxlength="40" pattern="[a-z][a-z0-9_-]{0,39}" />
            <x-page.field id="category-name" :restore-old-input="false" name="name" :label="__('Category name')" :value="$category?->name" required maxlength="100" />
            <x-page.field id="category-status" :restore-old-input="false" type="select" name="status" :label="__('Category status')" :value="$category?->status" :options="['active'=>__('Active'),'archived'=>__('Archived')]" />
            <x-m3.button type="submit">{{ __('Save vendor category') }}</x-m3.button>
        </fieldset>
    </form>
