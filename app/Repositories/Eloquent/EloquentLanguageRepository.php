<?php

namespace App\Repositories\Eloquent;

use App\Models\SpeakingLanguage;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentLanguageRepository implements LanguageRepositoryInterface
{
    public function speakingLanguages(): Collection
    {
        return SpeakingLanguage::orderBy('name')->get(['code', 'name']);
    }
}
