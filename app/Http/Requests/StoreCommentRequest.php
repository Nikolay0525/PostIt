<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Shape only. Whether this user may comment on this specific post (verified, not banned from
     * its group) is checked in the controller against the loaded post via CommentPolicy::create().
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => ['required', 'uuid'],
            'parent_id' => ['nullable', 'uuid'],
            'text' => ['required', 'string', 'max:500'],
        ];
    }
}
