<div class="report-section-title">{{ __('leave_history') }}</div>

<table class="table"
            id="table_list"
            data-toggle="table"
            data-pagination="true"
            data-search="true"
            data-show-refresh="true"
            data-show-columns="true"
            data-mobile-responsive="true"
            data-page-list="[5,10,20,50]"
            data-escape="false">

            <thead>
                <tr>
                    <th data-field="no">{{ __('no.') }}</th>
                    <th data-field="reason">{{ __('leave_reason') }}</th>
                    <th data-field="from_date">{{ __('from_date') }}</th>
                    <th data-field="to_date">{{ __('to_date') }}</th>
                    <th data-field="days">{{ __('total_days') }}</th>
                    <th data-field="status">{{ __('status') }}</th>
                </tr>
            </thead>

            <tbody>
                @foreach($leaveDetail as $leave)

                    @php
                        $days = isset($leave->leave_days)
                            ? $leave->leave_days
                            : \Carbon\Carbon::parse($leave->from_date)
                                ->diffInDays(\Carbon\Carbon::parse($leave->to_date)) + 1;
                    @endphp

                    <tr>
                        <td>{{ $loop->iteration }}</td>

                        <td>{{ $leave->reason ?? __('not_mentioned') }}</td>

                        <td>{{ date('d-m-Y',strtotime($leave->from_date)) }}</td>

                        <td>{{ date('d-m-Y',strtotime($leave->to_date)) }}</td>

                        <td>
                            <span class="badge badge-secondary p-2">
                                {{ $days }} {{ __('days') }}
                            </span>
                        </td>

                        <td>
                            @if($leave->status == 0)
                                <span class="badge badge-warning p-2">{{ __('pending') }}</span>
                            @elseif($leave->status == 1)
                                <span class="badge badge-success p-2">{{ __('approved') }}</span>
                            @else
                                <span class="badge badge-danger p-2">{{ __('rejected') }}</span>
                            @endif
                        </td>
                    </tr>

                @endforeach
            </tbody>

        </table>