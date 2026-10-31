<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

/**
 * The editable part of what StoreGroupRequest validates: the address and privacy are not changed
 * here. Whether this user may edit the group is checked in the controller via GroupPolicy::update().
 */
class UpdateGroupRequest extends StoreGroupRequest
{
    public function rules(): array
    {
        return Arr::only(parent::rules(), [
            'name',
            'description',
            'language_code',
            'rules',
            'rules.*.text',
            'rules.*.example',
        ]);
    }
}
