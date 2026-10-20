<?php

namespace App\Http\Requests;

use App\Enums\Tour;
use App\Enums\TourStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTourRequest extends FormRequest
{
    // Only the signed-in user's own answer can be saved, and the route is behind `auth`.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tour' => ['required', Rule::enum(Tour::class)],
            'status' => ['required', Rule::enum(TourStatus::class)],
        ];
    }
}
