<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\uniqueLessonInClass;
use App\Rules\YouTubeUrl;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLessonRequest extends FormRequest
{
    public const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'gif'];

    public const VIDEO_EXTENSIONS = ['mp4', 'mov', 'avi', 'webm', 'mkv', 'flv', 'wmv', '3gp', 'm4v', 'ogv'];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function authorize(): bool
    {
        return auth()->user()?->can('lesson-create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', new uniqueLessonInClass($this->class_section_id, $this->subject_id)],
            'description' => 'required',
            'class_section_id' => 'required|numeric',
            'subject_id' => 'required|numeric',

            'file' => 'nullable|array',
            'file.*.type' => 'nullable|in:file_upload,youtube_link,video_upload,other_link',
            'file.*.name' => 'required_with:file.*.type',
            'file.*.thumbnail' => [
                'nullable',
                'required_if:file.*.type,youtube_link,video_upload,other_link',
                'image',
                'mimes:'.implode(',', self::IMAGE_EXTENSIONS),
            ],

            'file.*.file' => [
                'nullable',
                'required_if:file.*.type,file_upload,video_upload',
                'file',
                function (string $attribute, $value, $fail) {
                    if (! $value) {
                        return;
                    }

                    $index = explode('.', $attribute)[1] ?? null;
                    $type = $this->input("file.{$index}.type");
                    $extension = strtolower((string) $value->getClientOriginalExtension());

                    if ($type === 'file_upload' && ! in_array($extension, self::DOCUMENT_EXTENSIONS, true)) {
                        $fail(trans('please_upload_a_valid_document_file', [
                            'types' => implode(', ', self::DOCUMENT_EXTENSIONS),
                        ]));
                    }

                    if ($type === 'video_upload' && ! in_array($extension, self::VIDEO_EXTENSIONS, true)) {
                        $fail(trans('please_upload_a_valid_video_file', [
                            'types' => implode(', ', self::VIDEO_EXTENSIONS),
                        ]));
                    }
                },
            ],

            'file.*.link' => ['nullable', 'required_if:file.*.type,youtube_link', new YouTubeUrl],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => trans('lesson_name_is_required'),
            'description.required' => trans('description_is_required'),
            'class_section_id.required' => trans('class_section_is_required'),
            'subject_id.required' => trans('subject_is_required'),
            'file.*.name.required_with' => trans('file_name_is_required'),
            'file.*.thumbnail.required_if' => trans('please_select_a_thumbnail_for_this_file_type'),
            'file.*.thumbnail.image' => trans('please_select_valid_image'),
            'file.*.thumbnail.mimes' => trans('please_select_valid_image'),
            'file.*.file.required_if' => trans('please_upload_a_file'),
            'file.*.file.file' => trans('please_upload_a_valid_file'),
            'file.*.link.required_if' => trans('please_enter_a_valid_link'),
        ];
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'error' => true,
            'message' => trans('no_permission_message'),
        ]));
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => true,
            'message' => $validator->errors()->first(),
        ]));
    }
}
