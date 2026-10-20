@extends('layouts.master')

@section('title')
    {{ __('import_questions') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('import_questions') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <form class="pt-3" id="create-form-bulk-questions" enctype="multipart/form-data"
                            action="{{ route('online-exam-question.store-bulk') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('session_years') }} <span class="text-danger">*</span></label>
                                    <select required name="session_year_id" id="session_year_id"
                                        class="form-control select2 online-exam-session-year" style="width:100%;"
                                        tabindex="-1" aria-hidden="true">
                                        @foreach ($session_years as $years)
                                            <option value="{{ $years->id }}"{{ $years->default == 1 ? 'selected' : '' }}>
                                                {{ $years->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('class') }} <span class="text-danger">*</span></label>
                                    <select required name="class_id" class="form-control select2 online-exam-class-id"
                                        style="width:100%;" tabindex="-1" aria-hidden="true">
                                        <option value="">--- {{ __('select') . ' ' . __('class') }} ---</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}">
                                                {{ $class->name }} {{ $class->medium->name }}
                                                {{ $class->streams->name ?? '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('subject') }} <span class="text-danger">*</span></label>
                                    <select required name="subject_id" class="form-control select2 online-exam-subject-id"
                                        style="width:100%;" tabindex="-1" aria-hidden="true">
                                        <option value="">--- {{ __('select') . ' ' . __('subject') }} ---</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('file_upload') }} <span class="text-danger">*</span></label>
                                    <input type="file" name="file" class="file-upload-default" />
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" disabled=""
                                            placeholder="{{ __('file_upload') }}" required="required" />
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme"
                                                type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-xs-12">
                                    <input class="btn btn-theme submit_bulk_file" type="submit"
                                        value="{{ __('submit') }}" name="submit" id="submit_bulk_file">
                                </div>
                            </div>
                        </form>
                        <hr>
                        <div class="form-group col-12 col-md-3 mt-4">
                            <a class="btn btn-theme form-control"
                                href="{{ route('online-exam-question.download-sample') }}" download>
                                <strong>{{ __('download_sample_file') }}</strong>
                            </a>
                        </div>
                        <div class="col-sm-12 col-xs-12">
                            <span style="font-size: 14px"> <b>{{ __('Note') }} :- </b>{{ __('bulk_import_questions_note') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
@endsection
