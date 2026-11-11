<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkPostsSeenRequest extends FormRequest
{
    // The browser sends what it gathered since the last batch (every few seconds), so a real
    // batch is a screenful or two; the cap only stops an oversized request.
    public const MAX_IDS = 100;

    // Only the signed-in user's own "seen" is noted, and the route is behind `auth`.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'max:'.self::MAX_IDS],
            'ids.*' => ['uuid', 'distinct'],
        ];
    }
}
