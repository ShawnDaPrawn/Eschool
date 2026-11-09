{{-- Contributions of this school: the teacher's own, or what a leader or content lead looks after. --}}
<div class="m3-card m3-card--outlined"><div class="page-table__scroll" role="region" aria-label="{{ __('Shared questions') }}" tabindex="0">
<table class="page-table__table">
    <caption class="m3-visually-hidden">{{ __('Shared questions') }}</caption>
    <thead><tr><th scope="col">{{ __('Question') }}</th><th scope="col">{{ __('Topic') }}</th>@if ($decide)<th scope="col">{{ __('Shared by') }}</th>@endif<th scope="col">{{ __('Credit') }}</th><th scope="col">{{ __('Where it stands') }}</th><th scope="col">{{ __('Ratings') }}</th><th scope="col">{{ __('Actions') }}</th></tr></thead>
    <tbody>
    @foreach ($rows as $row)
        <tr>
            <td dir="auto">@if ($row['library_item_id'] !== null && $teacher)<a href="{{ route('shared-library.items.show', $row['library_item_id']) }}">{{ $row['title'] }}</a>@else{{ $row['title'] }}@endif</td>
            <td>{{ $row['topic'] ? $row['topic']['subject_code'].' · '.$row['topic']['grade'].' · '.$row['topic']['name'] : '' }}</td>
            @if ($decide)<td>{{ $row['mine'] ? __('you') : $row['teacher'] }}</td>@endif
            <td>{{ $row['credit_label'] }}</td>
            <td>{{ $row['status_label'] }}@if ($row['decline_reason']): {{ $row['decline_reason'] }}@endif</td>
            <td>{{ $row['rating'] === null ? __('Not enough ratings yet') : number_format($row['rating']['average'], 1).' · '.$row['rating']['count'] }}</td>
            <td>
                @if ($decide && $row['can_decide'])
                    <form method="POST" action="{{ route('shared-library.contributions.approve', $row['id']) }}" class="page-inline-form">@csrf
                        <button class="m3-button m3-button--outlined" type="submit">{{ __('Approve') }}</button></form>
                    <form method="POST" action="{{ route('shared-library.contributions.decline', $row['id']) }}" class="page-inline-form">@csrf
                        <x-page.field name="reason" id="decline-{{ $row['id'] }}" type="text" :label="__('Reason for not approving')" minlength="10" maxlength="500" :restore-old-input="false" required />
                        <button class="m3-button m3-button--text" type="submit">{{ __('Do not approve') }}</button></form>
                @endif
                @if ($row['can_withdraw'])
                    <form method="POST" action="{{ route('shared-library.contributions.withdraw', $row['id']) }}" class="page-inline-form">@csrf
                        <button class="m3-button m3-button--text" type="submit" aria-label="{{ __('Withdraw :title', ['title' => $row['title']]) }}">{{ __('Withdraw') }}</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div></div>
