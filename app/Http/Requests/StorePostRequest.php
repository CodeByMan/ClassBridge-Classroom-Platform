<?php

namespace App\Http\Requests;

use App\Enums\PostType;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');

        return $group !== null && $this->user()->can('store', [Post::class, $group]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:32'],
            'content' => ['nullable', 'string', 'max:1024'],
            'type' => ['required', 'integer', Rule::enum(PostType::class)],
            'deadline' => ['nullable', 'date'],
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
