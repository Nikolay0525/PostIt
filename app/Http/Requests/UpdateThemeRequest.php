<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThemeRequest extends FormRequest
{
    // Only the signed-in user's own theme can be changed, and the route is behind `auth`.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dark' => ['required', 'boolean'],
        ];
    }
}
