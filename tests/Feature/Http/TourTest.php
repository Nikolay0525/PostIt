<?php

namespace Tests\Feature\Http;

use App\Enums\Tour;
use App\Enums\TourStatus;
use App\Models\User;
use App\Models\UserSettings;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_a_new_user_has_answered_no_tour(): void
    {
        $user = User::factory()->create();

        foreach (Tour::cases() as $tour) {
            $this->assertNull($user->settings->tourStatus($tour));
        }

        $this->actingAs($user)->get('/')
            ->assertInertia(fn ($page) => $page->where('tours', []));
    }

    public function test_an_answer_is_saved_and_shared_with_every_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/settings/tours', ['tour' => 'navigation', 'status' => 'completed'])
            ->assertNoContent();

        $this->assertSame(TourStatus::Completed, UserSettings::find($user->id)->tourStatus(Tour::Navigation));
        $this->actingAs($user)->get('/')
            ->assertInertia(fn ($page) => $page->where('tours', ['navigation' => 'completed']));
    }

    public function test_answering_one_tour_keeps_the_others_and_can_change_its_own_answer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/settings/tours', ['tour' => 'navigation', 'status' => 'declined']);
        $this->actingAs($user)->patchJson('/settings/tours', ['tour' => 'feed', 'status' => 'completed']);
        $this->actingAs($user)->patchJson('/settings/tours', ['tour' => 'navigation', 'status' => 'completed']);

        $this->assertSame(
            ['navigation' => 'completed', 'feed' => 'completed'],
            UserSettings::find($user->id)->tours,
        );
    }

    public function test_unknown_tours_and_statuses_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson('/settings/tours', ['tour' => 'cooking', 'status' => 'completed'])
            ->assertJsonValidationErrors('tour');
        // "Later" is never stored.
        $this->actingAs($user)->patchJson('/settings/tours', ['tour' => 'navigation', 'status' => 'later'])
            ->assertJsonValidationErrors('status');
    }

    public function test_guests_get_no_tours_and_cannot_answer(): void
    {
        $this->get('/')->assertInertia(fn ($page) => $page->where('tours', null));

        $this->patchJson('/settings/tours', ['tour' => 'navigation', 'status' => 'completed'])
            ->assertUnauthorized();
    }
}
