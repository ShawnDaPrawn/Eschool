@extends('layouts.master')

@section('title')
    {{ __('holiday') }}
@endsection

@section('content')
    <div class="content-wrapper m3 page-content">
        <x-page.header :title="__('manage') . ' ' . __('holiday')" />

        <x-panel-view-nav :current="request('view') === 'create' ? 'create' : 'list'" :items="[
 'list'=>['label'=>__('School holidays'),'href'=>request()->url()],
 'create'=>['label'=>'Create new','href'=>request()->url().'?view=create'],
 ]" />
<div class="panel-form-grid">
            @if (Auth::user()->can('holiday-create'))
                @if(request('view') === 'create')
<div class="  ">
                    <div class="m3-card m3-card--outlined">
                        <div class="m3-card__content panel-record-card">
                            <h4 class="card-title">
                                {{ __('create') . ' ' . __('holiday') }}
                            </h4>
                            <form class="create-form pt-3" id="formdata" action="{{ url('holiday') }}" method="POST"
                                novalidate="novalidate">
                                @csrf
                                <div class="panel-form-grid">
                                    <div class="form-group  ">
                                        <label>{{ __('session_year') }} <span class="text-danger">*</span></label>
                                        <select required name="session_year_id" id="session_year_id" class="form-control">
                                            <option value="">{{ __('select') . ' ' . __('session_years') }}</option>
                                            @foreach ($session_years as $session_year)
                                                <option value="{{ $session_year->id }}"
                                                    data-start-date="{{ date('d-m-Y', strtotime($session_year->start_date)) }}"
                                                    data-end-date="{{ date('d-m-Y', strtotime($session_year->end_date)) }}">
                                                    {{ $session_year->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group  ">
                                        <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('date', null, [
                                            'required',
                                            'placeholder' => __('date'),
                                            'class' => 'holiday-datepicker form-control',
                                            'id' => 'holiday_date',
                                            'disabled' => 'disabled',
                                        ]) !!}
                                        <span class="input-group-addon input-group-append">
                                        </span>
                                    </div>
                                    <div class="form-group  ">
                                        <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                        {!! Form::text('title', null, ['required', 'placeholder' => __('title'), 'class' => 'form-control']) !!}

                                    </div>
                                </div>
                                <div class="panel-form-grid">
                                    <div class="form-group  ">
                                        <label>{{ __('description') }}</label>
                                        {!! Form::textarea('description', null, [
                                            'rows' => '2',
                                            'placeholder' => __('description'),
                                            'class' => 'form-control',
                                        ]) !!}

                                    </div>
                                </div>
                                <input class="m3-button m3-button--filled" type="submit" value={{ __('submit') }}>
                            </form>
                        </div>
                    </div>
                </div>
@endif
            @endif
            @if (Auth::user()->can('holiday-list'))
                @if(request('view') !== 'create')
<div class="  ">
                    <div class="m3-card m3-card--outlined">
                        <div class="m3-card__content panel-record-card">
                            <h4 class="card-title">
                                {{ __('list') . ' ' . __('holiday') }}
                            </h4>
                            <div class="panel-form-grid">
                                <div class="">
                                    <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                                        data-url="{{ url('holiday-list') }}" data-click-to-select="true"
                                        data-side-pagination="server" data-pagination="true"
                                        data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                        data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true"
                                        data-fixed-columns="true" data-trim-on-search="false" data-mobile-responsive="true"
                                        data-sort-name="id" data-sort-order="desc" data-maintain-selected="true"
                                        data-export-types='["txt","excel"]'
                                        data-export-options='{ "fileName": "holiday-list-<?= date('d-m-y') ?>
                                        ","ignoreColumn": ["operate"]}'
                                        data-query-params="holidayListQueryParams" data-escape="true">
                                        <thead>
                                            <tr>
                                                <th scope="col" data-field="id" data-sortable="true"
                                                    data-visible="false">
                                                    {{ __('id') }}
                                                </th>
                                                <th scope="col" data-field="no" data-sortable="false">
                                                    {{ __('no.') }}
                                                </th>
                                                <th scope="col" data-field="date" data-width="150" data-sortable="false">
                                                    {{ __('date') }}
                                                </th>
                                                <th scope="col" data-field="title" data-sortable="false">
                                                    {{ __('title') }}
                                                </th>
                                                <th scope="col" data-field="description" data-sortable="false">
                                                    {{ __('description') }}
                                                </th>
                                                @if (Auth::user()->can('holiday-edit') || Auth::user()->can('holiday-delete'))
                                                    <th data-escape="false" data-events="actionEvents" data-width="150"
                                                        scope="col" data-field="operate" data-sortable="false">
                                                        {{ __('action') }}
                                                    </th>
                                                @endif
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
@endif
            @endif
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('holiday') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="edit-event" class="edit-event" action="{{ route('holiday.update', 1) }}"
                    novalidate="novalidate">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="id">
                        <div class="row form-group">
                            <div class=" ">
                                <label>{{ __('date') }} <span class="text-danger">*</span></label>
                                {!! Form::text('date', null, [
                                    'required',
                                    'placeholder' => __('date'),
                                    'class' => 'datepicker-popup form-control',
                                    'id' => 'date',
                                ]) !!}
                                <span class="input-group-addon input-group-append">
                                </span>
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class=" ">
                                <label>{{ __('title') }} <span class="text-danger">*</span></label>
                                {!! Form::text('title', null, [
                                    'required',
                                    'placeholder' => __('title'),
                                    'class' => 'form-control',
                                    'id' => 'title',
                                ]) !!}
                            </div>
                        </div>
                        <div class="row form-group">
                            <div class=" ">
                                <label>{{ __('description') }}</label>
                                {!! Form::textarea('description', null, [
                                    'placeholder' => __('description'),
                                    'class' => 'form-control',
                                    'id' => 'description',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="m3-button m3-button--outlined"
                            data-dismiss="modal">{{ __('close') }}</button>
                        <input class="m3-button m3-button--filled" type="submit" value={{ __('edit') }} />
                    </div>
                </form>
            </div>
        </div>
    </div>


@endsection

@section('script')
    <script>
        window.actionEvents = {
            'click .editdata': function(e, value, row, index) {
                $('#id').val(row.id);
                $('#date').val(row.date);
                $('#title').val(row.title);
                $('#description').val(row.description);
            }
        };
    </script>

    <script type="text/javascript">
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search
            };
        }

        $(document).ready(function () {
            $('#session_year_id').on('change', function () {
                var selected = $(this).find('option:selected');
                var startDate = selected.data('start-date');
                var endDate = selected.data('end-date');
                var $datepicker = $('#holiday_date');

                if (startDate && endDate) {
                    $datepicker.prop('disabled', false);
                    $datepicker.val('');
                    initDatePicker($datepicker, { minDate: startDate, maxDate: endDate });
                } else {
                    $datepicker.val('');
                    $datepicker.prop('disabled', true);
                    if ($datepicker[0] && $datepicker[0]._flatpickr) {
                        $datepicker[0]._flatpickr.destroy();
                    }
                }
            });
        });
    </script>
@endsection
