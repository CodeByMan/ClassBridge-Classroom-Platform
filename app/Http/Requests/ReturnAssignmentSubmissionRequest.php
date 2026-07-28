<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReturnAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission !== null && $this->user()->can('returnWork', $submission);
    }

    public function rules(): array
    {
        return [
            'returned' => ['required', 'boolean'],
        ];
    }
}
