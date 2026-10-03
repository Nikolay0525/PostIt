<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    public const MAX_SIZE_KB = 2048;

    public const MAX_DIMENSION = 4096;

    // Only the signed-in user's own avatar can be changed, and the route is behind `auth`.
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The file is stored as uploaded (no resizing, see ImageService), so size and dimensions are
     * capped here. `mimes` checks the file's contents, not the name the client sent.
     */
    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:'.self::MAX_SIZE_KB,
                'dimensions:max_width='.self::MAX_DIMENSION.',max_height='.self::MAX_DIMENSION,
            ],
        ];
    }
}
