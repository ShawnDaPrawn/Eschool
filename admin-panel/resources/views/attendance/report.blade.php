@extends('layouts.master')

@section('title')
    {{ __('attendance') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">

            <h3 class="page-title">
                {{ __('attendance_report') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('view') . ' ' . __('attendance_report') }}
                        </h4>
                        <div class="row">
                            <div class="form-group col-sm-12 col-md-2">
                                <label>{{ __('class') }} {{ __('section') }}</label>
                                <select required name="class_section_id" id="filter_class_section_id"
                                    class="form-control select2" style="width:100%;" tabindex="-1" aria-hidden="true">
                                    <option value="">{{ __('select') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="show_attendance_student_list">
                            <table aria-describedby="mydesc" class='table student_table' id='table_list' data-toggle="table"
                                data-url="{{ url('student-attendance-report') }}" data-click-to-select="true"
                                data-side-pagination="server" data-pagination="true"
                                data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="true" data-toolbar="#toolbar"
                                data-show-columns="true" data-show-refresh="true" data-fixed-columns="true"
                                data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                data-sort-order="desc" data-maintain-selected="true" data-export-types='["txt","excel"]'
                                data-show-export="true"
                                data-export-options='{ "fileName": "view-attendance-list-<?= date('d-m-y') ?>"
                                ,"ignoreColumn": ["operate"]}'
                                data-query-params="queryParams" data-escape="true">
                                <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false"  data-switchable="false">
                                            {{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                        <th scope="col" data-field="user_id" data-sortable="true" data-visible="false"  data-switchable="false">
                                            {{ __('user_id') }}</th>
                                        <th scope="col" data-field="student_id" data-escape="false" data-sortable="true"
                                            data-visible="false" data-switchable="false">{{ __('student_id') }}</th>
                                        <th scope="col" data-field="name" data-sortable="false" data-escape="false"
                                            data-width="260" data-min-width="240"
                                            data-formatter="studentInfoFormatter">{{ __('student') }}</th>
                                        <th scope="col" data-field="admission_no" data-sortable="true"
                                            data-visible="false">
                                            {{ __('admission_no') }}</th>
                                        <th scope="col" data-field="roll_no" data-sortable="true">{{ __('roll_no') }}
                                        </th>
                                        <th scope="col" data-field="total_days" data-sortable="false">
                                            {{ __('total_days') }}</th>
                                        <th scope="col" data-field="present_days" data-sortable="false">
                                            {{ __('present_days') }}</th>
                                        <th scope="col" data-field="absent_days" data-sortable="false">
                                            {{ __('absent_days') }}</th>
                                        <th scope="col" data-field="percentage" data-sortable="false">
                                            {{ __('percentage') }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                'session_year_id': $('#filter_session_year_global').val(),
                'class_section_id': $('#filter_class_section_id').val(),
                'term_id': $('#filter_class_section_id').find('option:selected').attr('data-term') || '',
            };
        }

        function loadAttendanceReportClassSectionsWithTerms() {
            var session_year_id = $('#filter_session_year_global').val();
            var isTeacher = {{ Auth::user()->hasRole('Teacher') ? 'true' : 'false' }};

            if (!session_year_id) {
                $('#filter_class_section_id').html('<option value="">{{ __('select') }}</option>').trigger(
                    'change.select2');
                return;
            }

            var requestData = {
                session_year_id: session_year_id
            };

            if (isTeacher) {
                requestData.scope = 'teacher';
            }

            $.ajax({
                url: "{{ url('get-class-sections-with-terms') }}",
                type: "GET",
                data: requestData,
                success: function(response) {
                    var html = '<option value="">{{ __('select') }}</option>';

                    for (var i = 0; i < response.length; i++) {
                        var label = response[i].label ? response[i].label : '';
                        if (!label) {
                            label = response[i].name || '';
                        }

                        html += '<option value="' + response[i].class_section_id + '"' +
                            ' data-class="' + response[i].class_id + '"' +
                            ' data-term="' + (response[i].term_id || '') + '"' +
                            '>' + label + '</option>';
                    }

                    $('#filter_class_section_id').html(html).val('').trigger('change.select2');
                }
            });
        }

        $('#filter_session_year_global').on('change', function() {
            loadAttendanceReportClassSectionsWithTerms();
            $('#table_list').bootstrapTable('refresh', {
                silent: true
            });
        });

        $('#filter_class_section_id').on('change', function() {
            $('#table_list').bootstrapTable('refresh', {
                silent: true
            });
        });

        $(document).ready(function() {
            loadAttendanceReportClassSectionsWithTerms();
        });
    </script>
@endsection
