@extends('layouts.master')

@section('title')
    {{ __('leave') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
           
            <h3 class="page-title">
                
                
                {{ __('manage') . ' ' . __('leave') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-sm-12 col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create') . ' ' . __('leave') }}
                        </h4>

                        <form action="{{ route('leave.store') }}" id="create-leave" class="create-leave pt-3"
                            novalidate="novalidate">
                            @csrf
                            <div class="row">
                                {!! Form::hidden('leave_master_id', $leaveMaster->id ?? '', ['class' => 'form-control']) !!}
                                {{-- holiday --}}
                                {!! Form::hidden('holiday_days', $holiday_days ?? '', ['class' => 'form-control holiday_days']) !!}
                                {!! Form::hidden('public_holiday', $public_holiday ?? '', ['class' => 'form-control public_holiday']) !!}

                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('reason') }} <span class="text-danger">*</span></label>
                                    <textarea name="reason" required id="" class="form-control" placeholder="{{ __('reason') }}"></textarea>
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('from_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('from_date', null, [
                                        'required',
                                        'id' => 'from_date',
                                        'class' => 'form-control leave-date',
                                        'placeholder' => __('from_date'),
                                        'autocomplete' => 'off',
                                    ]) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-3">
                                    <label>{{ __('to_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('to_date', null, [
                                        'required',
                                        'id' => 'to_date',
                                        'class' => 'form-control leave-date',
                                        'placeholder' => __('to_date'),
                                        'autocomplete' => 'off',
                                    ]) !!}
                                </div>

                                <div class="form-group col-sm-6 col-md-6">
                                    <label>{{ __('attachments') }} <span class="text-small text-info">
                                            ({{ __('upload_multiple_files') }})</span></label>
                                    <input type="file" multiple name="files[]" id="uploadInput"
                                        class="file-upload-default" />
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info"
                                            placeholder="{{ __('files') }}" aria-label="" />
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme"
                                                type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12 col-md-12 leave_dates mt-3">

                                </div>
                            </div>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </form>

                    </div>
                </div>
            </div>

            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-danger">{{ __('note') }} :
                            {{ __('To modify an existing leave, kindly delete the old entry and submit a new request') }}.
                        </h6>
                        <h4 class="card-title">{{ __('my') . ' ' . __('leaves') }}</h4>
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-2">
                                <label for="filter" class="filter-menu">{{ __('filter') }}</label>
                                {!! Form::select(
                                    'filter_day',
                                    [
                                        'All' => trans('All'),
                                        'Today' => trans('today'),
                                        'Tomorrow' => trans('tomorrow'),
                                        'Upcoming' => trans('upcoming'),
                                    ],
                                    'All',
                                    ['class' => 'form-control', 'id' => 'filter_upcoming'],
                                ) !!}
                            </div>

                            <div class="form-group col-sm-12 col-md-2">
                                <label for="month" class="filter-menu">{{ __('month') }}</label>
                                {!! Form::select('month', $months, null, [
                                    'class' => 'form-control',
                                    ' id' => 'filter_month_id',
                                    'placeholder' => __('all'),
                                ]) !!}
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                            data-url="{{ route('leave.show', 1) }}" data-click-to-select="true"
                            data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                            data-search="true" data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                            data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                            data-sort-order="desc" data-maintain-selected="true" data-export-data-type='all'
                            data-query-params="leaveQueryParams" data-toolbar="#toolbar"
                            data-export-options='{ "fileName": "leave-list-<?= date('d-m-y') ?>"
                            ,"ignoreColumn":["operate"]}' data-show-export="true" data-escape="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                        {{ __('id') }}
                                    </th>
                                    <th scope="col" data-field="no">{{ __('no.') }}</th>
                                    <th scope="col" data-field="from_date">{{ __('from_date') }}</th>
                                    <th scope="col" data-field="to_date">{{ __('to_date') }}</th>
                                    <th scope="col" data-field="days">{{ __('total') }}</th>
                                    <th scope="col" data-field="reason" data-formatter="reasonFormatter">
                                        {{ __('reason') }}
                                    </th>
                                    <th scope="col" data-field="file" data-formatter="fileFormatter">
                                        {{ __('attachments') }}
                                    </th>
                                    <th scope="col" data-field="status" data-formatter="leaveStatusFormatter">
                                        {{ __('status') }}
                                    </th>
                                    <th scope="col" data-field="created_at">{{ __('created_at') }}</th>
                                    <th scope="col" data-field="updated_at">{{ __('updated_at') }}</th>
                                    <th scope="col" data-escape="false" data-field="operate"
                                        data-events="leavesEvents">{{ __('action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">

                        <h5 class="modal-title" id="exampleModalLabel"> {{ __('view') . ' ' . __('leave') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fa fa-close"></i></span>
                        </button>
                    </div>
                    <form id="formdata" class="edit-form" action="{{ url('leave') }}" novalidate="novalidate">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" name="id" id="id">
                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('reason') }} <span class="text-danger">*</span></label>
                                    <textarea name="reason" disabled id="edit_reason" class="form-control" placeholder="{{ __('reason') }}"></textarea>
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('from_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('from_date', null, [
                                        'required',
                                        'class' => 'form-control datepicker-popup datepicker-popup-no-past',
                                        'placeholder' => __('from_date'),
                                        'id' => 'edit_from_date',
                                        'disabled' => true,
                                    ]) !!}
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-sm-12 col-md-12">
                                    <label>{{ __('to_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('to_date', null, [
                                        'required',
                                        'class' => 'form-control datepicker-popup datepicker-popup-no-past',
                                        'placeholder' => __('to_date'),
                                        'id' => 'edit_to_date',
                                        'disabled' => true,
                                    ]) !!}
                                </div>
                            </div>

                            <div class="form-group col-sm-12 col-md-12">
                                <label>{{ __('attachments') }} </label>
                                <div id="attachment"></div>
                            </div>

                            <div class="form-group col-sm-12 col-md-12 edit_leave_dates mt-3"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ __('Cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        // Session year boundaries (from PHP, formatted as DD-MM-YYYY for bootstrap-datepicker)
        var sessionStartDate = moment("{{ $currentSessionYear->start_date }}", 'YYYY-MM-DD').format('DD-MM-YYYY');
        var sessionEndDate = moment("{{ $currentSessionYear->end_date }}", 'YYYY-MM-DD').format('DD-MM-YYYY');
            var isSuperAdmin = @json(Auth::user()->hasRole('Super Admin'));
        function formSuccessFunction() {
            setTimeout(() => {
                $('.leave_dates').slideUp(500);
            }, 1000);
        }

        $(document).ready(function() {

            // ── FROM DATE picker ──────────────────────────────────────────────
            // Destroy first in case anything initialised it already without bounds
            initDatePicker($('#from_date'), {
                minDate: sessionStartDate, // must be >= session start
                maxDate: sessionEndDate, // must be <= session end
                onChange: function(selectedDates, selectedFromDate) {
                    // Keep to_date constrained to [from_date, session end]
                    setDatePickerBounds($('#to_date'), selectedFromDate || sessionStartDate,
                        sessionEndDate);

                    // If the currently selected to_date is before the new from_date, clear it
                    var currentToDate = $('#to_date').val();
                    if (currentToDate && selectedFromDate) {
                        var fromMoment = moment(selectedFromDate, 'DD-MM-YYYY');
                        var toMoment = moment(currentToDate, 'DD-MM-YYYY');
                        if (toMoment.isBefore(fromMoment)) {
                            setDatePickerValue($('#to_date'), '');
                        }
                    }
                },
            });

            // ── TO DATE picker ────────────────────────────────────────────────
            // Destroy first in case anything initialised it already without bounds
            // initDatePicker($('#to_date'), {
            //     minDate: sessionStartDate, // defaults to session start until from_date chosen
            //     maxDate: sessionEndDate, // must be <= session end
            // });

            var today = moment().format('DD-MM-YYYY');

var minDate = isSuperAdmin
    ? sessionStartDate
    : today;

initDatePicker($('#from_date'), {
    minDate: minDate,
    maxDate: sessionEndDate,
    onChange: function(selectedDates, selectedFromDate) {
        setDatePickerBounds($('#to_date'), selectedFromDate || minDate, sessionEndDate);
    }
});

initDatePicker($('#to_date'), {
    minDate: minDate,
    maxDate: sessionEndDate,
});

            // Guard: prevent manual editing of to_date that violates constraints
            $('#to_date').on('blur', function() {
                var fromVal = $('#from_date').val();
                var toVal = $(this).val();

                if (!toVal) return;

                var sessionStart = moment(sessionStartDate, 'DD-MM-YYYY');
                var sessionEnd = moment(sessionEndDate, 'DD-MM-YYYY');
                var fromMoment = fromVal ? moment(fromVal, 'DD-MM-YYYY') : sessionStart;
                var toMoment = moment(toVal, 'DD-MM-YYYY');

                if (toMoment.isBefore(fromMoment)) {
                    toastr.error("{{ __('invalid_date_range') }}");
                    setDatePickerValue($(this), '');
                } else if (toMoment.isBefore(sessionStart) || toMoment.isAfter(sessionEnd)) {
                    toastr.error("{{ __('outside_session_year') }}");
                    setDatePickerValue($(this), '');
                }
            });

            // Guard: prevent manual editing of from_date that violates constraints
            $('#from_date').on('blur', function() {
                var fromVal = $(this).val();
                if (!fromVal) return;

                var sessionStart = moment(sessionStartDate, 'DD-MM-YYYY');
                var sessionEnd = moment(sessionEndDate, 'DD-MM-YYYY');
                var fromMoment = moment(fromVal, 'DD-MM-YYYY');

                if (fromMoment.isBefore(sessionStart) || fromMoment.isAfter(sessionEnd)) {
                    toastr.error("{{ __('outside_session_year') }}");
                    setDatePickerValue($(this), '');
                }
            });
        });

     $(document).on('change', '#filter_upcoming, #filter_month_id', function () {
    $('#table_list').bootstrapTable('refresh');
});
    </script>
@endsection
