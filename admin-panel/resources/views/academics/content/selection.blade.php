<input type="hidden" name="selection[subject_id]" value="{{ $selection['subject_id'] }}">
<input type="hidden" name="selection[grade_level_id]" value="{{ $selection['grade_level_id'] }}">
<input type="hidden" name="selection[session_year_id]" value="{{ $selection['session_year_id'] }}">
<x-page.field :restore-old-input="false" :id="$fieldPrefix.'-title'" name="selection[title]" :label="__('Content title')" :value="$selection['title']" required minlength="3" maxlength="191" />
<x-page.field :restore-old-input="false" type="textarea" :id="$fieldPrefix.'-summary'" name="selection[summary]" :label="__('Short summary')" :value="$selection['summary']" maxlength="2000" />
<fieldset><legend>{{ __('Published teaching groups') }}</legend>
@foreach($groups as $g)<label class="content-choice"><input type="checkbox" name="selection[group_ids][]" value="{{ $g['id'] }}" @checked(in_array($g['id'],$selection['group_ids'],true))>{{ $g['name'] }}</label>@endforeach
</fieldset>
<fieldset><legend>{{ __('Curriculum topic tags') }}</legend>
@forelse($topics as $topic)<label class="content-choice"><input type="checkbox" name="selection[topic_ids][]" value="{{ $topic['id'] }}" @checked(in_array($topic['id'],$selection['topic_ids'],true))>{{ $topic['name'] }} · {{ __('Version') }} {{ $topic['curriculum_version'] }}</label>
@empty<p>{{ __('No published curriculum topics for this teaching context yet. You may continue without tags.') }}</p>@endforelse
</fieldset>
