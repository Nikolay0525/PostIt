<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVerdictRequest extends FormRequest
{
    public const REASON_MAX_LENGTH = 1000;

    public const MAX_MARKS = 20;

    public const QUOTE_MAX_LENGTH = 500;

    /**
     * Shape only. Whether this user may decide this case is checked in the controller (GroupPolicy::
     * moderate(), and the case being this group's and still open); whether the rule exists and the
     * marks are in the content, by ModerationVerdictService.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // "No violation" punishes nobody: it names no rule, marks nothing and needs no reason, so what
        // a form still sends for those is dropped rather than refused.
        return [
            'type' => ['required', Rule::in(['no_violation', 'remove', 'warning'])],
            'rule_index' => ['exclude_if:type,no_violation', 'required', 'integer', 'min:0'],
            'marks' => ['exclude_if:type,no_violation', 'required', 'array', 'min:1', 'max:'.self::MAX_MARKS],
            // Where the mark is: a post's title or its body; a comment has only a body. Left out, the body.
            'marks.*.field' => ['exclude_if:type,no_violation', 'nullable', Rule::in(['title', 'body'])],
            'marks.*.start' => ['exclude_if:type,no_violation', 'required', 'integer', 'min:0'],
            'marks.*.end' => ['exclude_if:type,no_violation', 'required', 'integer', 'min:1'],
            'marks.*.quote' => ['exclude_if:type,no_violation', 'required', 'string', 'max:'.self::QUOTE_MAX_LENGTH],
            'reason' => ['exclude_if:type,no_violation', 'required', 'string', 'max:'.self::REASON_MAX_LENGTH],
        ];
    }
}
