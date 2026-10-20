<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\uniqueTopicInLesson;
use App\Rules\YouTubeUrl;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateLessonTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('topic-edit') ?? false;
    }

    public function rules(): array
    {
        return [
            'edit_id' => 'required|numeric|exists:lesson_topics,id',
            'name' => ['required', new uniqueTopicInLesson($this->lesson_id, $this->edit_id)],
            'description' => 'required',

            // Existing files being edited - everything optional; validated only if provided.
            'edit_file' => 'nullable|array',
            'edit_file.*.type' => 'nullable|in:file_upload,youtube_link,video_upload,other_link',
            'edit_file.*.name' => 'nullable|required_with:edit_file.*.type',
            'edit_file.*.thumbnail' => [
                'nullable',
                'image',
                'mimes:'.implode(',', StoreLessonRequest::IMAGE_EXTENSIONS),
            ],
            'edit_file.*.file' => [
                'nullable',
                'file',
                $this->fileExtensionValidator(),
            ],
            'edit_file.*.link' => [
                'nullable',
                'required_if:edit_file.*.type,youtube_link',
                new YouTubeUrl,
            ],

            // New files being added during the edit.
            'file' => 'nullable|array',
            'file.*.type' => 'nullable|in:file_upload,youtube_link,video_upload,other_link',
            'file.*.name' => 'nullable|required_with:file.*.type',
            'file.*.thumbnail' => [
                'nullable',
                'required_if:file.*.type,youtube_link,video_upload,other_link',
                'image',
                'mimes:'.implode(',', StoreLessonRequest::IMAGE_EXTENSIONS),
            ],
            'file.*.file' => [
                'nullable',
                'required_if:file.*.type,file_upload,video_upload',
                'file',
                $this->fileExtensionValidator(),
            ],
            'file.*.link' => ['nullable', 'required_if:file.*.type,youtube_link', new YouTubeUrl],
        ];
    }

    public function messages(): array
    {
        return [
            'edit_id.required' => trans('topic_not_found'),
            'edit_id.exists' => trans('topic_not_found'),
            'name.required' => trans('topic_name_is_required'),
            'description.required' => trans('description_is_required'),

            'edit_file.*.name.required_with' => trans('file_name_is_required'),
            'edit_file.*.thumbnail.image' => trans('please_select_valid_image'),
            'edit_file.*.thumbnail.mimes' => trans('please_select_valid_image'),
            'edit_file.*.file.file' => trans('please_upload_a_valid_file'),
            'edit_file.*.link.required_if' => trans('please_enter_a_valid_link'),

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

    private function fileExtensionValidator(): \Closure
    {
        return function (string $attribute, $value, $fail) {
            if (! $value) {
                return;
            }

            // Attribute format: "edit_file.0.file" or "file.0.file".
            $parts = explode('.', $attribute);
            $base = $parts[0];
            $index = $parts[1] ?? null;
            $type = $this->input("{$base}.{$index}.type");

            $extension = strtolower((string) $value->getClientOriginalExtension());

            if ($type === 'file_upload' && ! in_array($extension, StoreLessonRequest::DOCUMENT_EXTENSIONS, true)) {
                $fail(trans('please_upload_a_valid_document_file', [
                    'types' => implode(', ', StoreLessonRequest::DOCUMENT_EXTENSIONS),
                ]));
            }

            if ($type === 'video_upload' && ! in_array($extension, StoreLessonRequest::VIDEO_EXTENSIONS, true)) {
                $fail(trans('please_upload_a_valid_video_file', [
                    'types' => implode(', ', StoreLessonRequest::VIDEO_EXTENSIONS),
                ]));
            }
        };
    }
}
