<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\HasEntityAttributes;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use HasEntityAttributes;

    protected string $attributeEntity = 'user';

    public function rules(): array
    {
        return [
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'date_of_birth' => 'required|date|before:today',
        ];
    }
}
