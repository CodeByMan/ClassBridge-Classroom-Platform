<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post !== null && $this->user()->can('store', [Comment::class, $post]);
    }

    public function rules(): array
    {
        return ['content' => ['required', 'string', 'max:512']];
    }
}
