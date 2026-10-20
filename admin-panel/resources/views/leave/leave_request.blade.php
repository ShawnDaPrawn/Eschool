@extends('layouts.master')

@section('title')
{{ __('leave') }}
@endsection
{{-- In <head> --}}
@push('styles')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

@endpush
@section('content')
<div class="content-wrapper">
   
    <div class="page-header">
      
        <h3 class="page-title">
            {{ __('manage') . ' ' . __('leave') }}
            
        </h3>
    </div>
    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ __('staff') . ' ' . __('leaves') }}</h4>
                    {!! Form::hidden('holiday_days', $holiday_days ?? '', ['class' => 'form-control holiday_days']) !!}
                    {!! Form::hidden('public_holiday', $public_holiday ?? '', ['class' => 'form-control public_holiday']) !!}

                    <div class="row align-items-end">
                        <div class="form-group col-sm-12 col-md-2">
                            <label for="" class="filter-menu">{{ __('filter') }}</label>
                            {!! Form::select(
                            'filter',
                            ['All' => __('all'), 'Today' => __('today'), 'Tomorrow' => __('tomorrow'), 'Upcoming' => __('upcoming')],
                            'All',
                            ['class' => 'form-control', 'id' => 'filter_upcoming'],
                            ) !!}
                        </div>

                        <div class="form-group col-sm-12 col-md-2">
                            <label for="month" class="filter-menu">{{ __('month') }}</label>
                            {!! Form::select('month', $months, null, [
                            'class' => 'form-control',
                            'id' => 'filter_month_id',
                            'placeholder' => __('all'),
                            ]) !!}
                        </div>

                        <div class="form-group col-sm-12 col-md-2">
                            <label for="staff" class="filter-menu">{{ __('staff') }}</label>
                            <select name="staff_id" class="form-control" id="filter_staff_id">
                                <option value="">{{ __('all') }}</option>
                                @foreach ($users as $user)
                                <option value="{{ $user->user?->id }}">
                                    {{ $user->user?->first_name . ' ' . $user->user?->last_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>


                        <div class="col-sm-12 col-md-6 d-flex justify-content-md-end justify-content-start align-items-end mb-3">
                            <!-- Updated padding, border-radius, and font properties to match your layout perfectly -->
                            <button type="button" class="btn font-weight-bold px-4 py-2 text-white"
                                data-toggle="modal" data-target="#createLeaveModal"
                                style="background-color: #21567d; border: none; border-radius: 6px;">
                                {{ __('create_leave') }}
                            </button>

                        </div>
                    </div>
                    <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                        data-url="{{ route('leave-request.show') }}" data-click-to-select="true"
                        data-side-pagination="server" data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]"
                        data-search="true" data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                        data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                        data-sort-name="id" data-sort-order="desc" data-maintain-selected="true" data-show-export="true"
                        data-export-options='{"fileName": "leave-request-list-<?= date('d-m-y') ?>","ignoreColumn":
                            ["operate"]}' data-query-params="leaveRequestQueryParams" data-escape="true">

                        <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="true" data-visible="false">
                                    {{ __('id') }}
                                </th>
                                <th scope="col" data-field="no">{{ __('no.') }}</th>
                                <th scope="col" data-field="name">{{ __('name') }}</th>
                                <th scope="col" data-field="from_date">{{ __('from_date') }}</th>
                                <th scope="col" data-field="to_date">{{ __('to_date') }}</th>
                                <th scope="col" data-field="days">{{ __('total') }}</th>
                                <th scope="col" data-field="reason" data-formatter="reasonFormatter">
                                    {{ __('reason') }}
                                </th>
                                <th scope="col" data-field="file" data-formatter="fileFormatter">
                                    {{ __('attachments') }}
                                </th>
                                <th scope="col" data-formatter="leaveStatusFormatter" data-field="status">
                                    {{ __('status') }}
                                </th>
                                <th scope="col" data-visible="false" data-field="created_at">{{ __('created_at') }}
                                </th>
                                <th scope="col" data-visible="false" data-field="updated_at">{{ __('updated_at') }}
                                </th>
                                <th scope="col" data-escape="false" data-field="operate" data-events="leavesEvents">
                                    {{ __('action') }}
                                </th>
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
                <form id="status-update" class="status-update" action="{{ url('leave-request-update') }}"
                    novalidate="novalidate" method="Post">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id">
                        <div class="row">
                            {{-- <div class="form-group col-sm-12 col-md-4">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input leave-status" name="status"
                                            id="optionsRadios2" value="0" checked=""> {{ __('pending') }}
                                        <i class="input-helper"></i></label>
                                </div>
                            </div> --}}

                            <div class="form-group col-sm-12 col-md-4">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input leave-status" name="status"
                                            id="optionsRadios2" value="1"> {{ __('approved') }} <i
                                            class="input-helper"></i></label>
                                </div>
                            </div>

                            <div class="form-group col-sm-12 col-md-4">
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="radio" class="form-check-input leave-status" name="status"
                                            id="optionsRadios2" value="2"> {{ __('rejected') }} <i
                                            class="input-helper"></i></label>
                                </div>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class="col-sm-12 col-md-12">
                                <label>{{ __('reason') }} <span class="text-danger">*</span></label>
                                <textarea name="reason" disabled id="edit_reason" class="form-control"></textarea>
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

                        <div class="row">
                            <div class="form-group col-sm-12 col-md-12 edit_leave_dates mt-3">

                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ __('Cancel') }}</button>
                        <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createLeaveModal" tabindex="-1" role="dialog" aria-labelledby="createLeaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none;">

            <div class="modal-header" style="background-color: #21567d; padding: 18px 24px;">
                <h5 class="modal-title font-weight-bold text-white" id="createLeaveModalLabel">
                    {{ __('create') . ' ' . __('leave') }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"
                    style="opacity: 1; font-size: 22px;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body" style="background-color: #f0f4f8; padding: 28px 24px;">

                <form id="createLeaveForm" enctype="multipart/form-data" novalidate="novalidate">
                    @csrf


                    <input type="hidden" name="leave_master_id" value="{{ $leaveMaster->id ?? '' }}">
                    <input type="hidden" name="holiday_days" class="holiday_days" value="{{ $holiday_days ?? '' }}">
                    <input type="hidden" name="public_holiday" class="public_holiday" value="{{ $public_holiday ?? '' }}">


                    <div class="form-group">
                        <label for="modal_user_id">{{ __('staff') }} <span class="text-danger">*</span></label>
                        <select name="staff_id" id="modal_user_id" class="form-control" required
                            style="border-radius: 8px; border: 1px solid #dde3ea; background: #fff; padding: 10px 14px; height: auto;">
                            <option value="">{{ __('select') . ' ' . __('staff') }}</option>
                            @foreach ($modalUsers as $modalUser)
                            <option value="{{ $modalUser->id }}">
                                {{ $modalUser->first_name . ' ' . $modalUser->last_name }}
                            </option>
                            @endforeach
                        </select>
                        <small id="modal_staff_name_display" class="text-muted mt-1 d-block"></small>
                    </div>


                    <div class="form-group">
                        <label for="modal_reason">{{ __('reason') }} <span class="text-danger">*</span></label>
                        <textarea name="reason" id="modal_reason" required rows="3" class="form-control"
                            placeholder="{{ __('reason') }}"
                            style="border-radius: 8px; border: 1px solid #dde3ea; background: #fff; padding: 10px 14px;"></textarea>
                    </div>


                    <div class="form-group">
                        <label for="modal_from_date">{{ __('from_date') }} <span class="text-danger">*</span></label>
                        <input type="text" name="from_date" id="modal_from_date" required
                            class="form-control" placeholder="{{ __('from_date') }}" autocomplete="off"
                            style="border-radius: 8px; border: 1px solid #dde3ea; background: #fff; padding: 10px 14px;">
                    </div>

                    <div class="form-group">
                        <label for="modal_to_date">{{ __('to_date') }} <span class="text-danger">*</span></label>
                        <input type="text" name="to_date" id="modal_to_date" required
                            class="form-control" placeholder="{{ __('to_date') }}" autocomplete="off"
                            style="border-radius: 8px; border: 1px solid #dde3ea; background: #fff; padding: 10px 14px;">
                    </div>

                    {{-- Leave days breakdown --}}
                    <div class="leave_dates mt-2" id="modal_leave_dates"></div>
                    {{-- Attachments --}}
                    <div class="form-group mb-0">
                        <label>
                            {{ __('attachments') }}
                            <span class="text-info" style="font-size: 13px; font-weight: 500;">
                                ({{ __('upload_multiple_files') }})
                            </span>
                        </label>
                        {{-- name="files[]" matches what store() expects --}}
                        <input type="file" multiple name="files[]" id="modal_uploadInput" class="file-upload-default" />
                        <div class="input-group">
                            <input type="text" class="form-control file-upload-info" placeholder="{{ __('files') }}" readonly
                                style="border-radius: 8px 0 0 8px; border: 1px solid #dde3ea; background: #fff; padding: 10px 14px;">
                            <div class="input-group-append">
                                <button class="file-upload-browse btn font-weight-bold text-white mb-0" type="button"
                                    style="background-color: #21567d; border-radius: 0 8px 8px 0; padding: 10px 20px;">
                                    {{ __('upload') }}
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer" style="background-color: #fff; border-top: 1px solid #e8edf2; padding: 16px 24px;">
                <button type="button" class="btn btn-light px-4 font-weight-bold" data-dismiss="modal"
                    style="border: 1px solid #dde3ea; border-radius: 8px; color: #555;">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn font-weight-bold px-4 text-white" id="submitLeaveBtn"
                    style="background-color: #21567d; border-radius: 8px; border: none;">
                    {{ __('submit') }}
                </button>
            </div>

        </div>
    </div>
</div>


@endsection

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    var fromPicker, toPicker;
    
    
    function leaveRequestQueryParams(params) {
    return {
        limit: params.limit,
        offset: params.offset,
        sort: params.sort,
        order: params.order,
        search: params.search,
        session_year_id: $('#filter_session_year_global').val(),
        filter_upcoming: $('#filter_upcoming').val(),
        month_id: $('#filter_month_id').val(),
        user_id: $('#filter_staff_id').val(),
    };
}

function updateModalLeaveDates() {
    var fromDateStr = $('#modal_from_date').val(); 
    var toDateStr = $('#modal_to_date').val();     

    if (!fromDateStr || !toDateStr) {
        $('#modal_leave_dates').empty();
        return;
    }

    var holiday_days = $('.holiday_days').val();
    holiday_days = holiday_days ? holiday_days.split(',') : [];
    var public_holiday = $('.public_holiday').val();

    var html = date_list(fromDateStr, toDateStr, '#modal_leave_dates', '#modal_to_date', '', holiday_days, public_holiday);
    $('#modal_leave_dates').html(html);
}

$('#createLeaveModal').on('shown.bs.modal', function() {
    fromPicker = flatpickr('#modal_from_date', {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disableMobile: true,
        onChange: function(selectedDates, dateStr) {
            if (toPicker) {
                toPicker.set('minDate', dateStr);
                if ($('#modal_to_date').val() && $('#modal_to_date').val() < dateStr) {
                    toPicker.clear();
                }
            }
            updateModalLeaveDates();
        }
    });

    toPicker = flatpickr('#modal_to_date', {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disableMobile: true,
        onChange: function() {
            updateModalLeaveDates();
        }
    });
});
    $('#createLeaveModal').on('hidden.bs.modal', function() {
    if (fromPicker) {
        fromPicker.destroy();
        fromPicker = null;
    }
    if (toPicker) {
        toPicker.destroy();
        toPicker = null;
    }
    $('#createLeaveForm')[0].reset();
    $('#modal_leave_dates').html('');
    $('#createLeaveModal .file-upload-info').val('');
    $('#modal_staff_name_display').text('');
});

    $('#createLeaveModal').on('hidden.bs.modal', function() {
        if (fromPicker) {
            fromPicker.destroy();
            fromPicker = null;
        }
        if (toPicker) {
            toPicker.destroy();
            toPicker = null;
        }
        $('#createLeaveForm')[0].reset();
        $('#createLeaveModal .leave_dates').html('');
        $('#createLeaveModal .file-upload-info').val('');
        $('#modal_staff_name_display').text('');
    });

    // rest of your existing submit logic stays unchanged ...
    $('#submitLeaveBtn').on('click', function() {
    var $btn = $(this);

    @if(!isset($leaveMaster) || !$leaveMaster)
        showErrorToast("{{ __('Kindly contact the school admin to configure Leave Settings for the current session year') }}");
        return;
    @endif

    var fromDateStr = $('#modal_from_date').val();
    var toDateStr = $('#modal_to_date').val();

    if (!$('#modal_user_id').val()) {
        alert("{{ __('Please select a staff member.') }}");
        return;
    }
    if (!$('#modal_reason').val().trim()) {
        alert("{{ __('Please enter a reason.') }}");
        return;
    }
    if (!fromDateStr) {
        alert("{{ __('Please select a from date.') }}");
        return;
    }
    if (!toDateStr) {
        alert("{{ __('Please select a to date.') }}");
        return;
    }
    if ($('#modal_leave_dates input[type="radio"]:checked').length === 0) {
        alert("{{ __('Please select leave type for the selected dates.') }}");
        return;
    }

    $btn.prop('disabled', true).text("{{ __('saving') }}...");

    var formData = new FormData($('#createLeaveForm')[0]);

    $.ajax({
        url: '{{ route("leave.store.admin") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        // success: function() {
        //     $('#createLeaveModal').modal('hide');
        //     $('#table_list').bootstrapTable('refresh');
        // },
        success: function(response) {
    $('#createLeaveModal').modal('hide');
    $('#table_list').bootstrapTable('refresh');
    showSuccessToast(response && response.message ? response.message : "{{ __('leave_added_successfully') }}");
},
        error: function(xhr) {
            var msg = "{{ __('something_went_wrong') }}";
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                else if (xhr.responseJSON.errors) {
                    var errors = xhr.responseJSON.errors;
                    msg = errors[Object.keys(errors)[0]][0];
                }
            }
            alert(msg);
        },
        complete: function() {
            $btn.prop('disabled', false).text("{{ __('submit') }}");
        }
    });
});



$('#filter_upcoming, #filter_month_id, #filter_staff_id, #filter_session_year_global').on('change', function () {
    $('#table_list').bootstrapTable('refresh', {
        silent: true
    });
});
</script>
@endsection