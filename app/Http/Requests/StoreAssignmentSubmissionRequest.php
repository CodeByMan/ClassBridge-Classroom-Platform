<?php

namespace App\Http\Requests;

use App\Rules\ValidZipArchive;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post !== null && $this->user()->can('submitFile', $post);
    }

    public function rules(): array
    {
        return [
            'submission_file' => ['required', 'file', 'mimes:zip', 'max:10240', new ValidZipArchive],
        ];
    }

    public function messages(): array
    {
        return [
            'submission_file.required' => 'Choose a ZIP file to submit.',
            'submission_file.mimes' => 'The submission must be a valid ZIP file.',
            'submission_file.max' => 'The ZIP file must not exceed 10 MB.',
        ];
    }
}
