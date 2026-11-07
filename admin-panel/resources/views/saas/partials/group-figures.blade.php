@if ($dashboard['schools'] === [])
    <p class="saas-card">{{ __('No school is in the group yet. A school appears here once its Super Admin agrees to join.') }}</p>
@else
<div class="saas-card saas-table-wrap" role="region" aria-label="{{ __('Figures across the group') }}" tabindex="0">
    <table class="saas-table">
        <thead><tr>
            <th scope="col">{{ __('School') }}</th>
            <th scope="col" class="num">{{ __('Learners') }}</th>
            <th scope="col" class="num">{{ __('Weekly active families') }}</th>
            <th scope="col" class="num">{{ __('Guardian reach') }}</th>
            <th scope="col" class="num">{{ __('Learners active in the app this week') }}</th>
            <th scope="col" class="num">{{ __('Attendance, last :days days', ['days' => $dashboard['attendance_days']]) }}</th>
        </tr></thead>
        <tbody>
        @foreach ($dashboard['schools'] as $row)
            <tr data-group-school="{{ $row['id'] }}">
                <th scope="row">{{ $row['name'] }}</th>
                <td class="num">{{ $show($row['learners']) }}</td>
                <td class="num">{{ $show($row['weekly_active_families']) }}</td>
                <td class="num">{{ $show($row['guardian_reach']) }}</td>
                <td class="num">{{ $show($row['active_learners']) }}</td>
                <td class="num">{{ $show($row['attendance']) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr data-group-total>
            <th scope="row">{{ trans_choice('{1} The group (1 school)|[2,*] The group (:count schools)', $dashboard['totals']['schools'], ['count' => $dashboard['totals']['schools']]) }}</th>
            <td class="num">{{ $show($dashboard['totals']['learners']) }}</td>
            <td class="num">{{ $show($dashboard['totals']['weekly_active_families']) }}</td>
            <td class="num">{{ $show($dashboard['totals']['guardian_reach']) }}</td>
            <td class="num">{{ $show($dashboard['totals']['active_learners']) }}</td>
            <td class="num">{{ $show($dashboard['totals']['attendance']) }}</td>
        </tr></tfoot>
    </table>
</div>
<p><small>{{ __('Computed :time.', ['time' => $dashboard['computed_at']]) }}</small></p>
@endif
