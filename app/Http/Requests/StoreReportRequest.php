<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public const TEXT_MAX_LENGTH = 100;

    /**
     * Shape only. Whether this user may report this specific post or comment is checked in the
     * controller via PostPolicy::report() / CommentPolicy::report().
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_type' => ['required', 'in:post,comment'],
            'target_id' => ['required', 'uuid'],
            // What was broken: one of the group's current rules (that it exists is checked against the
            // group's rules, in ReportService), or the platform's, or neither, which is "something
            // else" and needs a reason.
            'rule_index' => ['nullable', 'integer', 'min:0'],
            'platform_violation' => ['sometimes', 'boolean'],
            'text' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('rule_index') === null && ! $this->boolean('platform_violation')),
                'string',
                'max:'.self::TEXT_MAX_LENGTH,
            ],
        ];
    }
}
