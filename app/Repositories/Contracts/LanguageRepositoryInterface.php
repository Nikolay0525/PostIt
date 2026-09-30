<?php

namespace App\Repositories\Contracts;

use App\Models\SpeakingLanguage;
use App\Models\UiLanguage;
use Illuminate\Support\Collection;

interface LanguageRepositoryInterface
{
    /**
     * @return Collection<int, SpeakingLanguage> code, name, native_name; ordered by name
     */
    public function speakingLanguages(): Collection;

    /**
     * @return Collection<int, UiLanguage> active interface languages, ordered by name
     */
    public function uiLanguages(): Collection;

    /**
     * @return list<string> codes of the active interface languages
     */
    public function activeUiCodes(): array;

    /**
     * Which of these codes exist as speaking languages, in the given order.
     *
     * @param  list<string>  $codes
     * @return list<string>
     */
    public function existingSpeakingCodes(array $codes): array;
}
