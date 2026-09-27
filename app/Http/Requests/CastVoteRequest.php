<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CastVoteRequest extends FormRequest
{
    /**
     * Shape only. Whether this specific user may vote on this specific target (not their own,
     * not deleted) is checked in the controller against the loaded target via PostPolicy/CommentPolicy.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(['post', 'comment'])],
            'target_id' => ['required', 'uuid'],
            'positive' => ['required', 'boolean'],
        ];
    }
}
