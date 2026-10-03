<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    // The status emoji is picked from this set, not typed: the field can't carry arbitrary text,
    // and every option is known to render on common systems.
    public const STATUS_EMOJIS = [
        '😀', '😎', '🤔', '😴', '🤒', '🥳', '😤', '🥲',
        '💻', '🎮', '📚', '🎨', '🎵', '⚽', '🍳', '✈️',
        '🌴', '🏠', '🔥', '❤️', '🚀', '🌱', '☕', '🎯',
    ];

    public const STATUS_TEXT_MAX_LENGTH = 100;

    public const BIO_MAX_LENGTH = 500;

    // Only the signed-in user's own profile can be changed, and the route is behind `auth`.
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A partial update: only the fields sent are changed. Each may be cleared with null or an
     * empty string (the global middleware trims input and turns '' into null).
     */
    public function rules(): array
    {
        return [
            'status_emoji' => ['sometimes', 'nullable', 'string', Rule::in(self::STATUS_EMOJIS)],
            'status_text' => ['sometimes', 'nullable', 'string', 'max:'.self::STATUS_TEXT_MAX_LENGTH],
            'bio' => ['sometimes', 'nullable', 'string', 'max:'.self::BIO_MAX_LENGTH],
        ];
    }
}
