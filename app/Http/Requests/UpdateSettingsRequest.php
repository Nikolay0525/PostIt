<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public const MAX_SPEAKING_LANGUAGES = 10;

    // Only the signed-in user's own settings can be edited, and the route is behind `auth`.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ui_language_code' => ['required', 'string', Rule::exists('ui_languages', 'code')->where('is_active', true)],
            'speaking_languages' => ['present', 'array', 'max:'.self::MAX_SPEAKING_LANGUAGES],
            'speaking_languages.*' => ['string', 'distinct', Rule::exists('speaking_languages', 'code')],
            'dark_theme' => ['required', 'boolean'],
            'show_swear_words' => ['required', 'boolean'],
            'show_adult_content' => [
                'required',
                'boolean',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN) && ! $this->user()->isAdult()) {
                        $fail(__('validation.custom.show_adult_content.adult_only'));
                    }
                },
            ],
            'enable_cookies' => ['required', 'boolean'],
            'allow_messages' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array{ui_language_code: string, dark_theme: bool, show_swear_words: bool, show_adult_content: bool, enable_cookies: bool, allow_messages: bool}
     */
    public function settings(): array
    {
        return [
            'ui_language_code' => $this->validated('ui_language_code'),
            'dark_theme' => $this->boolean('dark_theme'),
            'show_swear_words' => $this->boolean('show_swear_words'),
            'show_adult_content' => $this->boolean('show_adult_content'),
            'enable_cookies' => $this->boolean('enable_cookies'),
            'allow_messages' => $this->boolean('allow_messages'),
        ];
    }
}
