<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserBioRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user !== null && $this->user()->can('update', $user);
    }

    public function rules(): array
    {
        return ['bio' => ['required', 'string', 'max:256']];
    }
}
