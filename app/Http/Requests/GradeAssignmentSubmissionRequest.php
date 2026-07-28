<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeAssignmentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission !== null && $this->user()->can('grade', $submission);
    }

    public function rules(): array
    {
        $totalMarks = max(1, (int) ($this->route('post')?->total_marks ?? 100));

        return [
            'grade' => ['required', 'integer', 'min:0', 'max:'.$totalMarks],
        ];
    }
}
