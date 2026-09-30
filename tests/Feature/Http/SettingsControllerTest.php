<?php

namespace Tests\Feature\Http;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\User;
use App\Models\UserSettings;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'ui_language_code' => 'en',
            'speaking_languages' => ['uk', 'de'],
            'dark_theme' => true,
            'show_swear_words' => true,
            'show_adult_content' => false,
            'enable_cookies' => true,
            'allow_messages' => false,
        ], $overrides);
    }

    private function speakingCodes(User $user): array
    {
        return DB::table('user_speaking_languages')->where('user_id', $user->id)->orderBy('language_code')->pluck('language_code')->all();
    }

    public function test_the_seeder_loads_every_iso_639_1_language(): void
    {
        $this->assertSame(183, DB::table('speaking_languages')->count());
        $this->assertDatabaseHas('speaking_languages', ['code' => 'uk', 'name' => 'Ukrainian', 'native_name' => 'українська']);
        $this->assertDatabaseHas('speaking_languages', ['code' => 'he', 'native_name' => 'עברית']);
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $this->get('/settings')->assertRedirect('/login');
        $this->patch('/settings', $this->payload())->assertRedirect('/login');
    }

    public function test_the_page_shows_the_users_current_settings_and_the_language_lists(): void
    {
        $user = User::factory()->create();
        DB::table('user_speaking_languages')->insert(['user_id' => $user->id, 'language_code' => 'uk']);

        $this->actingAs($user)->get('/settings')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Settings/Edit')
                ->where('settings.ui_language_code', 'uk')
                ->where('settings.speaking_languages', ['uk'])
                ->where('settings.allow_messages', true)
                ->where('settings.show_adult_content', false)
                ->where('can_enable_adult_content', true)
                ->has('ui_languages', 2)
                ->has('speaking_languages', 183)
                ->where('max_speaking_languages', UpdateSettingsRequest::MAX_SPEAKING_LANGUAGES));
    }

    public function test_settings_and_speaking_languages_are_saved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/settings', $this->payload())
            ->assertRedirect('/settings')
            ->assertSessionHas('status', 'settings-saved');

        $settings = UserSettings::find($user->id);
        $this->assertSame('en', $settings->ui_language_code);
        $this->assertTrue($settings->dark_theme);
        $this->assertTrue($settings->show_swear_words);
        $this->assertTrue($settings->enable_cookies);
        $this->assertFalse($settings->allow_messages);
        $this->assertSame(['de', 'uk'], $this->speakingCodes($user));
    }

    public function test_saving_replaces_the_previous_speaking_languages_and_may_clear_them(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/settings', $this->payload(['speaking_languages' => ['uk', 'de']]));
        $this->actingAs($user)->patch('/settings', $this->payload(['speaking_languages' => ['ja']]));
        $this->assertSame(['ja'], $this->speakingCodes($user));

        $this->actingAs($user)->patch('/settings', $this->payload(['speaking_languages' => []]))
            ->assertSessionHasNoErrors();
        $this->assertSame([], $this->speakingCodes($user));
    }

    public function test_an_adult_can_enable_adult_content(): void
    {
        $user = User::factory()->create(['date_of_birth' => now()->subYears(30)]);

        $this->actingAs($user)->patch('/settings', $this->payload(['show_adult_content' => true]))
            ->assertSessionHasNoErrors();

        $this->assertTrue(UserSettings::find($user->id)->show_adult_content);
    }

    public function test_a_minor_cannot_enable_adult_content(): void
    {
        $user = User::factory()->create(['date_of_birth' => now()->subYears(16)]);

        $this->actingAs($user)->get('/settings')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('can_enable_adult_content', false));

        $this->actingAs($user)->patch('/settings', $this->payload(['show_adult_content' => true]))
            ->assertSessionHasErrors('show_adult_content');

        $this->assertFalse(UserSettings::find($user->id)->show_adult_content);
        // The whole save is rejected, not just the one field.
        $this->assertSame('uk', UserSettings::find($user->id)->ui_language_code);
    }

    public function test_unknown_languages_duplicates_and_too_many_languages_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/settings', $this->payload(['speaking_languages' => ['xx']]))
            ->assertSessionHasErrors('speaking_languages.0');

        $this->actingAs($user)->patch('/settings', $this->payload(['speaking_languages' => ['uk', 'uk']]))
            ->assertSessionHasErrors('speaking_languages.0');

        $tooMany = DB::table('speaking_languages')->orderBy('code')->limit(UpdateSettingsRequest::MAX_SPEAKING_LANGUAGES + 1)->pluck('code')->all();
        $this->actingAs($user)->patch('/settings', $this->payload(['speaking_languages' => $tooMany]))
            ->assertSessionHasErrors('speaking_languages');

        $this->actingAs($user)->patch('/settings', $this->payload(['ui_language_code' => 'de']))
            ->assertSessionHasErrors('ui_language_code');

        $this->assertSame([], $this->speakingCodes($user));
    }

    public function test_registration_takes_speaking_languages_from_the_browser(): void
    {
        $this->withHeader('Accept-Language', 'uk-UA,uk;q=0.9,en-US;q=0.8,en;q=0.7,xx;q=0.5')
            ->post('/register', [
                'name' => 'Olena',
                'email' => 'olena@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'date_of_birth' => '1995-05-05',
            ])->assertSessionHasNoErrors();

        $user = User::where('email', 'olena@example.com')->firstOrFail();
        // Regions collapse onto the language, unknown codes are skipped.
        $this->assertSame(['en', 'uk'], $this->speakingCodes($user));
    }

    public function test_registration_falls_back_to_the_default_language_when_the_browser_sends_none_known(): void
    {
        $this->withHeader('Accept-Language', 'xx')
            ->post('/register', [
                'name' => 'Max',
                'email' => 'max@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'date_of_birth' => '1995-05-05',
            ])->assertSessionHasNoErrors();

        $user = User::where('email', 'max@example.com')->firstOrFail();
        $this->assertSame([config('app.default_speaking_language_code')], $this->speakingCodes($user));
    }
}
