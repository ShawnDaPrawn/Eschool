<section class="m3-card m3-card--outlined"><div class="m3-card__content">
<div class="panel-actions"><h2>{{ __('Award catalogue') }}</h2>
@if($canWrite && $canConfigure)<a class="m3-button m3-button--filled" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'definition','session_year_id'=>$year]) }}">{{ __('Create award') }}</a>@endif
</div>
<p>{{ __('A reusable award defines what is earned. Nominations, approval and presentation are separate steps for an eligible learner.') }}</p>
@forelse($config['award_definitions'] as $definition)
@php($category=collect($config['award_categories'])->firstWhere('id',$definition['award_category_id']))
<article class="recognition-entry">
<h3>{{ $definition['name'] }}</h3>
<p>{{ $category['name'] ?? __('Archived category') }} · {{ $definition['frequency']==='annual' ? __('Once per school year') : __('Repeatable achievement') }} · {{ $definition['active'] ? __('Active') : __('Archived') }}</p>
@if($category)<p>{{ __('Eligibility') }}: {{ __(ucfirst($category['scope_kind'])) }}@if($category['scope_kind']==='group') · {{ collect($groups)->firstWhere('id',$category['activity_group_id'])['name'] ?? __('Retained activity group') }}@elseif($category['scope_kind']==='grade') · {{ collect($config['grades'])->firstWhere('id',$category['grade_level_id'])['name'] ?? '' }}@if($category['subject_id']) · {{ collect($subjects)->firstWhere('id',$category['subject_id'])['name'] ?? '' }}@endif @endif</p>@endif
<p class="recognition-prose">{{ $definition['criteria'] }}</p>
<div class="panel-actions">
<a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'learners','session_year_id'=>$year]) }}">{{ __('Choose learner to nominate or present') }}</a>
@if($canWrite && ($category['can_manage'] ?? false))<a class="m3-button m3-button--text" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'definition','edit_id'=>$definition['id'],'session_year_id'=>$year]) }}">{{ __('Edit or archive award') }}</a>@endif
</div>
</article>
@empty
<x-page.empty-state :title="__('No award definitions yet')" :message="__('Create a category, then define an achievement such as U14 boys soccer Golden Boot and its criteria.')" />
@if($canWrite && $canConfigure)<a class="m3-button m3-button--outlined" href="{{ route('recognition.index',['module'=>$module,'view'=>'editor','task'=>'category','session_year_id'=>$year]) }}">{{ __('Create award category') }}</a>@endif
@endforelse
</div></section>
