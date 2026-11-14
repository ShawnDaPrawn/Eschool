@php
$firstDay = mktime(0, 0, 0, $calendarMonth, 1, $calendarYear);
$daysInMonth = date('t', $firstDay);
$startDay = date('w', $firstDay);
$monthName = date('F Y', $firstDay);
$day = 1;
@endphp

<div class="text-center mb-3">
    <h5>{{ $monthName }}</h5>
</div>

<div class="table-responsive report-table-wrap">
    <table class="sr-calendar-table">
        <caption class="m3-visually-hidden">{{ $monthName }} · {{ __('attendance') }}</caption>
        <thead>
            <tr>
                <th scope="col">{{ __('sun') }}</th>
                <th scope="col">{{ __('mon') }}</th>
                <th scope="col">{{ __('tue') }}</th>
                <th scope="col">{{ __('wed') }}</th>
                <th scope="col">{{ __('thu') }}</th>
                <th scope="col">{{ __('fri') }}</th>
                <th scope="col">{{ __('sat') }}</th>
            </tr>
        </thead>
        <tbody>
            @for($row = 0; $row < 6; $row++)
                <tr>
                @for($col = 0; $col < 7; $col++)
                    @if(($row==0 && $col < $startDay) || ($day > $daysInMonth))
                    <td class="sr-day-empty"></td>
                    @else
                    @php
                        $currentDate = sprintf('%04d-%02d-%02d', $calendarYear, $calendarMonth, $day);
                        $status = '';
                        $statusLabel = '';
                        $dayClass = '';
                        $statusClass = '';

                        if (isset($attendance[$currentDate])) {
                            switch ((int) $attendance[$currentDate]->type) {
                                case 1:
                                    $status = __('p');
                                    $statusLabel = __('present');
                                    $dayClass = 'sr-day-present';
                                    $statusClass = 'status-present';
                                    break;
                                case 3:
                                    $status = __('h');
                                    $statusLabel = __('holiday');
                                    $dayClass = 'sr-day-holiday';
                                    $statusClass = 'status-holiday';
                                    break;
                                case 4:
                                    $status = __('fh');
                                    $statusLabel = __('first_half_absent');
                                    $dayClass = 'sr-day-half';
                                    $statusClass = 'status-half';
                                    break;
                                case 5:
                                    $status = __('sh');
                                    $statusLabel = __('second_half_absent');
                                    $dayClass = 'sr-day-half';
                                    $statusClass = 'status-half';
                                    break;
                                default:
                                    $status = __('a');
                                    $statusLabel = __('absent');
                                    $dayClass = 'sr-day-absent';
                                    $statusClass = 'status-absent';
                                    break;
                            }
                        }
                    @endphp

                    <td class="{{ $dayClass }}" aria-label="{{ $currentDate }}: {{ $statusLabel ?: __('No attendance recorded') }}">
                        <div class="sr-day-number">{{ $day }}</div>

                        @if($status)
                        <div class="sr-day-status {{ $statusClass }}" title="{{ $statusLabel }}">{{ $status }}</div>
                        @endif
                    </td>
                    @php $day++; @endphp
                    @endif
                @endfor
                </tr>
            @endfor
        </tbody>
    </table>
</div>
