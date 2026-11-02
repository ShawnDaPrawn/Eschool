@extends('layouts.master')
@section('title', __('Exam coach memos'))
@section('content')
<div class="content-wrapper m3 page-content">
    <x-page.header :title="__('Exam coach memos')" :subtitle="__('Break each past paper memo into mark points: what earns each mark, its type, any rule and the marks it is worth. A memo is used for marking only after a teacher has reviewed it.')" />
    @if ($groups === [])
        <x-page.empty-state :title="__('No exam coach groups')" :message="__('You see your current teaching groups here once exam coach is switched on and you teach a group.')" />
    @else
        <nav aria-label="{{ __('Teaching groups') }}"><ul>
            @foreach ($groups as $group)
                <li>@if ($group['id'] === $group_id)<strong aria-current="page">{{ $group['name'] }} · {{ $group['subject_name'] }}</strong>@else<a href="{{ route('exam-memos.index', ['group' => $group['id']]) }}">{{ $group['name'] }} · {{ $group['subject_name'] }}</a>@endif</li>
            @endforeach
        </ul></nav>
        <section class="page-section" aria-labelledby="memo-papers">
            <h2 id="memo-papers">{{ __('Past papers') }}</h2>
            @if ($papers === [])
                <x-page.empty-state :title="__('No past papers yet')" :message="__('Papers added to exam coach for this group appear here.')" />
            @else
                <table class="page-table__table">
                    <thead><tr><th scope="col">{{ __('Paper') }}</th><th scope="col">{{ __('Paper status') }}</th><th scope="col">{{ __('Memo') }}</th><th scope="col"><span class="m3-visually-hidden">{{ __('Open') }}</span></th></tr></thead>
                    <tbody>
                        @foreach ($papers as $paper)
                            <tr>
                                <td dir="auto">{{ $paper['title'] }} · {{ $paper['paper_year'] }} {{ $paper['paper_code'] }}</td>
                                <td>{{ $paper['state'] === 'published' ? __('Published') : __('Draft') }}</td>
                                <td>{{ match ($paper['memo_state']) { 'reviewed' => __('Reviewed'), 'draft' => __('Mark points not reviewed'), default => __('No mark points yet') } }}</td>
                                <td><a href="{{ route('exam-memos.show', ['group' => $group_id, 'version' => $paper['version_id']]) }}">{{ __('Open the memo') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if ($page > 1)<a href="{{ route('exam-memos.index', ['group' => $group_id, 'page' => $page - 1]) }}">{{ __('Previous page') }}</a>@endif
                @if ($has_more)<a href="{{ route('exam-memos.index', ['group' => $group_id, 'page' => $page + 1]) }}">{{ __('Next page') }}</a>@endif
            @endif
        </section>
    @endif
</div>
@endsection
