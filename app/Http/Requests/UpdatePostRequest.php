<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Shape only. Whether this user may edit this specific post (its author, not banned from the
     * group) is checked in the controller against the loaded post via PostPolicy::update().
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:100'],
            'article' => ['required', 'string'],
        ];
    }
}
