<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppealRequest extends FormRequest
{
    public const STATEMENT_MAX_LENGTH = 500;

    /**
     * Shape only. Whether this user may appeal this verdict is checked in the controller, by
     * ModerationVerdictPolicy::appeal().
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statement' => ['required', 'string', 'max:'.self::STATEMENT_MAX_LENGTH],
        ];
    }
}
