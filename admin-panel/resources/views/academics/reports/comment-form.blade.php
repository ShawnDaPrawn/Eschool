@php
    $scope = $commentRow['subject_id'] === null ? 'homeroom' : 'subject:'.$commentRow['subject_id'];
    $fieldPrefix = 'report-comment-'.$commentRow['card_id'].'-'.str_replace(':', '-', $scope);
    $reviewed = $reviewedComment && $reviewedComment['card_id'] === $commentRow['card_id'] && ($reviewedComment['subject_id'] === null ? null : (int) $reviewedComment['subject_id']) === $commentRow['subject_id'] ? $reviewedComment : null;
    $input = $reviewed ?? $commentRow;
    if (!$reviewed && old('form_scope') === $fieldPrefix) $input = array_replace($input, ['text' => old('text', $input['text']), 'reason' => old('reason', ''), 'bank_id' => old('bank_id')]);
    $bankOptions = collect($configuration['comment_banks'])->filter(fn ($bank) => $bank['active'] && $bank['subject_id'] === $commentRow['subject_id'])->mapWithKeys(fn ($bank) => [$bank['id'] => $bank['label']])->all();
@endphp
<article class="m3-card m3-card--outlined report-comment"><div class="m3-card__content">
    <h3>{{ $commentRow['learner_name'] }} · {{ $commentRow['scope_label'] }}</h3><p>{{ $commentRow['period_name'] }}</p>
    @if($reviewed)<p role="status">{{ __('Your reviewed comment is still a draft. Save explicitly to apply it.') }}</p><p>{{ __('Latest saved') }}: {{ $commentRow['text'] }}</p>@endif
    <form method="POST" action="{{ route('academic-reports.comment', $commentRow['card_id']) }}" aria-label="{{ __('Comment for') }} {{ $commentRow['learner_name'] }} {{ $commentRow['scope_label'] }}">@csrf
        <input type="hidden" name="form_scope" value="{{ $fieldPrefix }}"><input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="subject_id" value="{{ $commentRow['subject_id'] }}"><input type="hidden" name="revision" value="{{ $input['revision'] }}"><input type="hidden" name="batch_revision" value="{{ $input['batch_revision'] }}"><input type="hidden" name="session_year_id" value="{{ $configuration['session_year_id'] }}"><input type="hidden" name="comment_page" value="{{ $comments['page'] }}">
        @if($batch)<input type="hidden" name="batch_id" value="{{ $batch['id'] }}">@endif
        <fieldset @disabled($conflict)><legend>{{ __('Report comment') }}</legend>
            <x-page.field name="text" type="textarea" :restore-old-input="false" :id="$fieldPrefix.'-text'" :label="__('Comment text')" :value="$input['text']" maxlength="3000" />
            @if(count($bankOptions))<x-page.field name="bank_id" type="select" :restore-old-input="false" :id="$fieldPrefix.'-bank'" :label="__('Use a comment bank instead')" :options="$bankOptions" :placeholder="__('Keep entered text')" :value="$input['bank_id'] ?? ''" />@endif
            @if($allowOverride)<x-page.field name="reason" :restore-old-input="false" :id="$fieldPrefix.'-reason'" :label="__('Correction reason')" :help="__('Required without this current teaching allocation. Stored privately in the audit.')" :value="$input['reason'] ?? ''" maxlength="500" />@endif
            <button type="submit" class="m3-button m3-button--filled">{{ __('Save comment') }}</button>
        </fieldset>
    </form>
</div></article>
