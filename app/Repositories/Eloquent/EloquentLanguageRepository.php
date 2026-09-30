<?php

namespace App\Repositories\Eloquent;

use App\Models\SpeakingLanguage;
use App\Models\UiLanguage;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentLanguageRepository implements LanguageRepositoryInterface
{
    public function speakingLanguages(): Collection
    {
        return SpeakingLanguage::orderBy('name')->get(['code', 'name', 'native_name']);
    }

    public function uiLanguages(): Collection
    {
        return UiLanguage::where('is_active', true)->orderBy('name')->get(['code', 'name']);
    }

    public function activeUiCodes(): array
    {
        return UiLanguage::where('is_active', true)->pluck('code')->all();
    }

    public function existingSpeakingCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }

        $existing = SpeakingLanguage::whereIn('code', $codes)->pluck('code')->all();

        return array_values(array_intersect($codes, $existing));
    }
}
