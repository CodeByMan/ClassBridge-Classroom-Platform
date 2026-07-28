<?php

namespace App\Http\Requests;

use App\Enums\PostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinishPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post !== null && $this->user()->can('finish', $post);
    }

    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'finished' => ['required', 'boolean'],
            'post_answer' => [
                'nullable',
                'string',
                'max:2048',
                Rule::requiredIf(
                    fn (): bool => $this->boolean('finished') && $post->type === PostType::TextAnswer
                ),
            ],
        ];
    }
}
