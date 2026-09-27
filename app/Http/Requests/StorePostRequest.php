<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Shape only. Whether this user may post in this specific group (verified, allowed in the
     * group, not banned from it) is checked in the controller against the loaded group via
     * PostPolicy::create().
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'uuid'],
            'title' => ['nullable', 'string', 'max:100'],
            'article' => ['required', 'string'],
        ];
    }
}
