<?php

namespace App\Services;

use App\Enums\Tour;
use App\Enums\TourStatus;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SettingsService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected GroupRepositoryInterface $groupRepository
    ) {}

    /**
     * @throws ModelNotFoundException when the user does not exist
     */
    public function getSettings(string $userId): User
    {
        return $this->userRepository->findForSettings($userId)
            ?? throw (new ModelNotFoundException)->setModel(User::class, [$userId]);
    }

    /**
     * Saves the preferences, replaces the speaking languages and sets which groups and people are
     * on the side panel, all together, so a failure never leaves one part saved.
     *
     * @param  array{ui_language_code: string, theme_mode: string, show_swear_words: bool, show_adult_content: bool, enable_cookies: bool, allow_messages: bool, amount_of_attached_subscriptions: int}  $settings
     * @param  list<string>  $speakingLanguageCodes
     * @param  list<string>  $attachedGroupIds
     * @param  list<string>  $attachedPeopleIds
     *
     * @throws InvalidArgumentException when a minor tries to enable adult content
     */
    public function updateSettings(User $user, array $settings, array $speakingLanguageCodes, array $attachedGroupIds, array $attachedPeopleIds): void
    {
        if ($settings['show_adult_content'] && ! $user->isAdult()) {
            throw new InvalidArgumentException('Adult content can only be enabled by users aged 18 or older.');
        }

        DB::transaction(function () use ($user, $settings, $speakingLanguageCodes, $attachedGroupIds, $attachedPeopleIds) {
            $this->userRepository->updateSettings($user->id, $settings);
            $this->userRepository->syncSpeakingLanguages($user->id, $speakingLanguageCodes);
            $this->groupRepository->syncAttachedOnFeed($user->id, $attachedGroupIds);
            $this->userRepository->syncFollowsAttachedOnFeed($user->id, $attachedPeopleIds);
        });
    }

    /**
     * The theme the user picked with the navbar button; it is what the 'manual' theme mode shows.
     */
    public function updateManualTheme(User $user, bool $dark): void
    {
        $this->userRepository->updateSettings($user->id, ['dark_theme' => $dark]);
    }

    // Answers to other tours are kept; answering the same tour again replaces the answer.
    public function answerTour(User $user, Tour $tour, TourStatus $status): void
    {
        // Read from the database, not from an already loaded relation, so an earlier answer is never lost.
        $current = $this->userRepository->findForSettings($user->id)->settings->tours ?? [];
        $tours = [...$current, $tour->value => $status->value];

        $this->userRepository->updateSettings($user->id, ['tours' => json_encode($tours)]);

        // Later reads in this request (e.g. the shared `tours` prop) see the new answer.
        $user->unsetRelation('settings');
    }
}
