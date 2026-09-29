<?php

namespace App\Repositories\Contracts;

use App\Models\SpeakingLanguage;
use Illuminate\Support\Collection;

interface LanguageRepositoryInterface
{
    /**
     * @return Collection<int, SpeakingLanguage> ordered by name
     */
    public function speakingLanguages(): Collection;
}
