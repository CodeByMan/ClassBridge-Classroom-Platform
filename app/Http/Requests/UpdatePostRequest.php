<?php

namespace App\Http\Requests;

use App\Enums\PostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post !== null && $this->user()->can('update', $post);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:32'],
            'content' => ['nullable', 'string', 'max:1024'],
            'type' => ['required', 'integer', Rule::enum(PostType::class)],
            'deadline_enabled' => ['required', 'boolean'],
            'deadline' => ['nullable', 'required_if:deadline_enabled,1', 'date'],
            'total_marks' => [
                'nullable',
                Rule::requiredIf(fn (): bool => (int) $this->input('type') === PostType::FileUpload->value),
                'integer',
                'min:1',
                'max:1000',
            ],
        ];
    }
}
