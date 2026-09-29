<?php

namespace App\Http\Requests;

use App\Services\GroupService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    public const MAX_RULES = 15;

    public const RULE_TEXT_MAX_LENGTH = 100;

    public const RULE_EXAMPLE_MAX_LENGTH = 300;

    /**
     * Shape only. Whether this user may create a group at all is checked in the controller via
     * GroupPolicy::create(), same split as StorePostRequest.
     */
    public function authorize(): bool
    {
        return true;
    }

    // "Retro-Gaming " is accepted as "retro-gaming" instead of being rejected over casing/spaces.
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('slug'))) {
            $this->merge(['slug' => strtolower(trim($this->input('slug')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'slug' => [
                'required',
                'string',
                'max:'.GroupService::SLUG_MAX_LENGTH,
                'regex:'.GroupService::SLUG_PATTERN,
                Rule::unique('groups', 'slug'),
            ],
            'description' => ['required', 'string', 'max:250'],
            'rules' => ['nullable', 'array', 'max:'.self::MAX_RULES],
            'rules.*.text' => ['required', 'string', 'max:'.self::RULE_TEXT_MAX_LENGTH],
            'rules.*.example' => ['nullable', 'string', 'max:'.self::RULE_EXAMPLE_MAX_LENGTH],
            'language_code' => ['required', 'string', Rule::exists('speaking_languages', 'code')],
            'is_private' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase Latin letters, digits and single hyphens between words (e.g. "retro-gaming").',
        ];
    }
}
